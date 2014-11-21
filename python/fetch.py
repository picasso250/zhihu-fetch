import socket
import errno

class Coroutine(object):
    """docstring for Coroutine"""
    def __init__(self):
        super(Coroutine, self).__init__()
        self.queue = []
        self.chunk_size = 1024
        self.done = []
        
    def from_del(self, will_delete):
        for w in will_delete:
            self.queue.remove(w)
    def append(self, host, url, callback):
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        if s is None:
            print("Could not create socket\n"); # 创建一个Socket
        port = 80
        print('connect', host, port)
        connection = s.connect((host, port))
        s.setblocking(False)
        print('id', len(self.queue))
        key = len(self.queue) # key, 4
        self.queue.append((host, url, callback, s, key)) # {'host': info[0], 'url': info[1], 'callback': info[2]}
        self.request(host, url, s)
    def request(self, host, url, s):
        headers = [
            "GET {} HTTP/1.1".format(url),
            "Host: {}".format(host),
            "Accept-Encoding: identity"
        ]
        print('send', headers)
        headers_str = "\r\n".join(headers)+"\r\n\r\n"
        s.sendall(headers_str.encode())
    def do_cycle(self):
        # print('do_cycle', len(self.queue))
        will_delete = None
        for host, url, callback, s, key in self.queue:
            try:
                b = s.recv(self.chunk_size)
            except socket.timeout as e:
                print('socket.timeout')
                continue
            except socket.error as e:
                err = e.args[0]
                if err == errno.EAGAIN or err == errno.EWOULDBLOCK:
                    continue
                else:
                    # a "real" error occurred
                    print(e)
                    raise e
            if len(b) == 0:
                print('server close, delete', key)
                s.close()
                callback.close()
                will_delete = (host, url, callback, s, key)
                break
            go = callback.send(b)
            assert go is not None
            if not go:
                print(host,url,'content end, delete', key)
                s.close()
                callback.close()
                self.done.append((host, url))
                will_delete = (host, url, callback, s, key)
                break
        if will_delete is not None:
            self.queue.remove(will_delete)
            self.do_cycle()
    def send(self, host, url, callback):
        print('work on', host, url)
        self.append(host, url, callback)
        self.do_cycle()
    def close(self):
        print('clean up')
        while len(self.queue) > 0:
            self.do_cycle()
        print('end')
        
def sender(f):
    def wrapper(*args, **kw):
        c = f(*args, **kw)
        next(c)
        return c
    return wrapper
@sender
def http(filename):
    def parse_header(raw_header):
        header_lines = raw_header.split("\r\n")
        code = int(header_lines[0].split(' ')[1])
        headers = {}
        for line in header_lines[1:]:
            pos = line.find(':')
            if pos != -1:
                key = line[0:pos]
                value = line[pos+1:].strip()
                headers[key] = value
        return code, headers
    ba = bytearray()
    with open(filename, 'wb') as f:
        raw = bytearray()
        i = 0
        while True:
            b = yield True
            if b is None or len(b) == 0:
                continue
            # print(filename, length, b)
            raw.extend(b)
            pos = raw.find(b"\r\n\r\n")
            if pos != -1:
                print('find header and body sep',filename)
                assert i == 0
                i+=1
                raw_header = raw[0:pos].decode()
                code, headers = parse_header(raw_header)
                print(filename,code, headers)
                left = raw[pos+len(b"\r\n\r\n"):]
                # print('left', left)
                if 'Content-Length' in headers:
                    l = f.write(left)
                    assert l == len(left)
                    ba.extend(left)
                    CL = int(headers['Content-Length'])
                    print('Content-Length',CL)
                    length = CL - len(left)
                    while True:
                        b = yield True
                        l = f.write(b)
                        assert l == len(b)
                        ba.extend(b)
                        length -= len(b)
                        if length <= 0:
                            assert length == 0
                            yield ba
                elif 'Transfer-Encoding' in headers:
                    TE = headers['Transfer-Encoding']
                    assert TE == 'chunked'
                    print('left',left)
                    pos = left.find(b"\r\n")
                    assert pos != -1
                    length = int(left[0:pos].decode(), 16)
                    left_bytes = left[pos+len(b"\r\n"):]
                    l = f.write(left_bytes)
                    assert l == len(left_bytes)
                    ba.extend(left_bytes)
                    length -= len(left_bytes)
                    while True:
                        b = yield True
                        # print('chunked',b)
                        if b.find(b"\r\n0\r\n\r\n") != -1:
                            b = b[0:len(b)-len(b"\r\n0\r\n\r\n")]
                            l = f.write(b)
                            assert l == len(b)
                            ba.extend(b)
                            yield ba
                        l = f.write(b)
                        assert l == len(b)
                        ba.extend(b)
                        length -= len(b)
                        if length <= 0:
                            print('length less than 0', length, b)
                            print('should start of digits',b[-length:])

@sender
def fetch_page(host, url, filename, after_fetch):
    def request(host, url, s):
        headers = [
            "GET {} HTTP/1.1".format(url),
            "Host: {}".format(host),
            "Accept-Encoding: identity"
        ]
        print('send', headers)
        headers_str = "\r\n".join(headers)+"\r\n\r\n"
        s.sendall(headers_str.encode())
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    port = 80
    print('connect', host, port)
    connection = s.connect((host, port))
    s.setblocking(False)
    request(host, url, s)
    handler = http(host+'.html')
    chunk_size = 1024
    while True:
        try:
            b = s.recv(chunk_size)
        except socket.timeout as e:
            print('socket.timeout')
            yield True
            continue
        except socket.error as e:
            err = e.args[0]
            if err == errno.EAGAIN or err == errno.EWOULDBLOCK:
                print('EAGAIN')
                yield True
                continue
            else:
                # a "real" error occurred
                print(e)
                raise e
        assert b is not None
        if len(b) == 0:
            print('server close, delete')
            s.close()
            yield False
        ba = handler.send(b)
        assert ba is not None
        if not isinstance(ba, bool):
            after_fetch(ba)
            yield False

def make_after_fetch():
    def after_fetch(ba):
        pass
    return after_fetch

pool = []
def cycle():
    if len(pool) > 0:
        will_delete = None
        for i in pool:
            if not next(i):
                will_delete = i
                break
        if will_delete is not None:
            pool.remove(will_delete)
            cycle()
def loop():
    while len(pool) > 0:
        cycle()
if __name__ == '__main__':
    a = fetch_page('www.baidu.com', '/', 'baidu.html', make_after_fetch())
    b = fetch_page('www.zhihu.com', '/', 'zhihu.html', make_after_fetch())
    pool = [a,b]
    loop()

def fetch_zhihu_page(url, after_fetch):
    pool.append(fetch_page('www.zhihu.com', url, 'zhihu.html', after_fetch))
def fetch_people_page(conn, username, page = 1):
    url = "/people/{}/answers".format(username)
    url_page = "{}?page={:d}".format(url, page)
    print("\n{}\t".format(url_page), end='')
    sys.stdout.flush()
    timer.timer()
    try:
        conn.request("GET", url_page)
    except socket.timeout as e:
        print('wow! timeout')
        raise e
    response = conn.getresponse()
    t = timer.timer()
    avg = int(get_average(t, 'user page'))
    code = response.status
    print("[{}]\t{} ms\tAvg: {} ms".format(code, t, avg))
    if code == 404:
        slog("user username fetch fail, code code")
        dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_FAIL})
        print( "没有这个用户", username)
        return None
    if code != 200:
        slog("user username fetch fail, code code")
        dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_FAIL})
        print( "奇奇怪怪的返回码", code)
        return None
    content = response.read()
    return content
