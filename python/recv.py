
import coroutine

@coroutine.sender
def http(callback):
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
                callback(left)
                CL = int(headers['Content-Length'])
                print('Content-Length',CL)
                length = CL - len(left)
                while True:
                    b = yield True
                    callback(b)
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
                    assert pos != -1, "can not find lead number in chunk begining "+repr(left)
                    length = int(left[0:pos].decode(), 16)
                    left_bytes = left[pos+len(b"\r\n"):]
                    callback(left_bytes)
                    length -= len(left_bytes)
                    while True:
                        b = yield True
                        # print('chunked',b)
                        if b.find(b"\r\n0\r\n\r\n") != -1:
                            b = b[0:len(b)-len(b"\r\n0\r\n\r\n")]
                            callback(b)
                            yield code, headers, ba
                        if length - len(b) <= 0:
                            # print('will less than 0', length, b)
                            tial = b[:length]
                            callback(left)
                            left = b[length+len(b"\r\n"):]
                            break
                        else:
                            callback(b)
                            length -= len(b)
