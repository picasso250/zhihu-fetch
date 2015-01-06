#coding: utf-8

import sys, threading
import http.client
import socket
import urllib.parse
import re
import html.parser
import logging
import recv
import errno
import zhihu
import dbhelper
import timer
import fetch
import coroutine

@coroutine.executor
def fetch_page(host, url, after_fetch):
    logging.info('start fetch %s%s', host, url)
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
    print(url)
    filename = 'last.html'
    ba = bytearray()
    def callback(b):
        with open(host+'.html', 'ab') as f:
            l = f.write(b)
            assert l == len(b)
            ba.extend(b)
    handler = recv.http(callback)
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

class HrefParser(html.parser.HTMLParser):
    def handle_starttag(self, tag, attrs):
        if tag == 'a':
            ad = dict(attrs)
            if 'href' in ad:
                href = ad['href']
                print(href)
                if href.find('http://') == 0:
                    p = urllib.parse.urlparse(href)
                    host = p.netloc
                    if p.path == '':
                        url = '/'
                    else:
                        url = p.path+'?'+p.query
                    test_fetch(host, url)
fetched = []
ignore_list = ['SFY', 'LFY', 'Set-Cookie', 'Expires', 'Date', 'ETag']
def test_fetch(host, url):
    if (host, url) in fetched:
        return
    fetched.append((host, url))
    def after(code, headers, content):
        print('we will assert', host, url)
        conn = http.client.HTTPConnection(host, timeout=7)
        conn.request("GET", url)
        response = conn.getresponse()
        assert code == response.status
        print(host, url, code)
        if code != 200:
            print('code', code)
            return
        for k,v in response.getheaders():
            assert k in headers
            if k not in ignore_list:
                assert headers[k] == v, k+': '+headers[k]+' ===== vs ===== '+v
        print(headers)
        if 'Content-type' not in headers:
            print('no Content-type')
            return
        Ct = headers['Content-type']
        if Ct.find('text/html') != 0:
            print('not html')
            return
        content2 = response.read()
        text1 = content.decode('utf-8', 'ignore')
        text2 = content2.decode('utf-8', 'ignore')
        regex = re.compile(r' id="cs[\d\w]+" ')
        clean_text1 = regex.sub(' id="cs" ', text1)
        clean_text2 = regex.sub(' id="cs" ', text2)

        with open(host+url.replace('/', '-') +'-clean_text1.html', 'w') as f:
            f.write(clean_text1)
        with open(host+url.replace('/', '-') +'-clean_text2.html', 'w') as f:
            f.write(clean_text2)
        assert clean_text1 == clean_text2, 'not equal {}{}'.format(host, url)
        parser = HrefParser()
        parser.feed(clean_text1)

    fetch_page(host, url, after)

logging.basicConfig(filename='app.log', level=logging.DEBUG)
test_fetch('www.hao123.com', '/')
while len(coroutine.pool) > 8:
    coroutine.cycle()
coroutine.loop()
print('Test OK')
