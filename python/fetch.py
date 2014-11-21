import socket
import errno
import sys
import time
import re
import logging
import zhihu
import dbhelper
import timer
import coroutine

@coroutine.sender
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
        def append(b):
            l = f.write(b)
            assert l == len(b)
            ba.extend(b)
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
                    append(left)
                    CL = int(headers['Content-Length'])
                    print('Content-Length',CL)
                    length = CL - len(left)
                    while True:
                        b = yield True
                        append(b)
                        length -= len(b)
                        if length <= 0:
                            assert length == 0
                            yield code, headers, ba
                elif 'Transfer-Encoding' in headers:
                    TE = headers['Transfer-Encoding']
                    assert TE == 'chunked'
                    while True:
                        print('left',left)
                        pos = left.find(b"\r\n")
                        assert pos != -1
                        length = int(left[0:pos].decode(), 16)
                        left_bytes = left[pos+len(b"\r\n"):]
                        append(left_bytes)
                        length -= len(left_bytes)
                        while True:
                            b = yield True
                            # print('chunked',b)
                            if b.find(b"\r\n0\r\n\r\n") != -1:
                                b = b[0:len(b)-len(b"\r\n0\r\n\r\n")]
                                append(b)
                                yield code, headers, ba
                            if length - len(b) <= 0:
                                # print('will less than 0', length, b)
                                tial = b[:length]
                                append(left)
                                left = b[length+len(b"\r\n"):]
                                break
                            else:
                                append(b)
                                length -= len(b)

@coroutine.executor
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
    handler = http(url.replace('/','-').replace('?','-').replace('=','-')+'.html')
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
        info = handler.send(b)
        assert info is not None
        if not isinstance(info, bool):
            code, headers, ba = info
            after_fetch(*info)
            yield False

def make_after_fetch():
    def after_fetch(code, headers, content):
        pass
    return after_fetch

def fetch_zhihu_page(url, after_fetch):
    fetch_page('www.zhihu.com', url, 'zhihu.html', after_fetch)
def saveAnswer(username, answer_link_list):
    regex = re.compile(r'^/question/(\d+)/answer/(\d+)')

    success_ratio = None
    avg = None
    for url in answer_link_list:
        matches = regex.search(url)
        if matches is None:
            raise Exception('url not good')
        qid = matches.group(1)
        aid = matches.group(2)
        zhihu.slog("\t{}".format(url))
        sys.stdout.flush()
        timer.timer('saveAnswer')
        def after_fetch(code, headers, content):
            if content is None:
                return
            t = timer.timer('saveAnswer')
            zhihu.slog("\t{} ms".format(t))
            if len(content) == 0:
                zhihu.slog("content is empty\n")
                zhihu.slog("url [code] empty")
                return False
            print('will parse',url)
            question, descript, content, vote = zhihu.parse_answer_pure(content)
            zhihu.slog("{}\t^{}\t{}".format(url, vote, question))

            dbhelper.saveQuestion(qid, question, descript)
            dbhelper._saveAnswer(aid, qid, username, content, vote)
        fetch_zhihu_page(url, after_fetch)

def people_page(username, page = 1):
    url = "/people/{}/answers".format(username)
    url_page = "{}?page={:d}".format(url, page)
    print("\n{}\t".format(url_page), end='')
    sys.stdout.flush()
    def after_fetch(code, headers, content):
        print("[{}]".format(code))
        if code == 404:
            slog("user username fetch fail, code code")
            dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_FAIL})
            print("没有这个用户", username)
            return None
        if code != 200:
            slog("user username fetch fail, code code")
            dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_FAIL})
            print("奇奇怪怪的返回码", code)
            return None
        if content is None:
            print('content is None')
            return
        
        src = zhihu.get_avatar_src(content)
        dbhelper.update_user_by_name(username, {'avatar': src})

        link_list = zhihu.get_answer_link_list(content)
        rs = saveAnswer(username, link_list)

        num = zhihu.get_page_num(content)
        if num > 1:
            for i in range(2, num):
                content = people_page(username, i)
                if content is None:
                    print('content is None on line 34')
                    continue
                link_list = zhihu.get_answer_link_list(content)
                zhihu.saveAnswer(username, link_list)
        dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_OK})
        zhihu.slog('### after saveAnswer ###')
    fetch_zhihu_page(url_page, after_fetch)

if __name__ == '__main__':
    fetch_page('www.baidu.com', '/', 'baidu.html', make_after_fetch())
    fetch_page('www.zhihu.com', '/', 'zhihu.html', make_after_fetch())
    coroutine.loop()
