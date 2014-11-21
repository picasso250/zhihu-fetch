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
import recv

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
    handler = recv.http(host+url.replace('/','-').replace('?','-').replace('=','-')+'.html')
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
        if code == 200:
            import dom
            dom.html2dom(content.decode())
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
