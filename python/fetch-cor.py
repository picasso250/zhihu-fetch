#coding: utf-8

import sys, threading
import http.client
import zhihu
import dbhelper
import timer

MAX_THREAD_NUM = 4

count = dbhelper.getNotFetchedUserCount()
print("there are", count, "user to fetch")

def fetch_user(username):
    dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_ING})
    conn = zhihu.get_conn()
    content = zhihu.fetch_people_page(conn, username)
    if content is None:
        print('content is None')
        conn.close()
        return
    
    src = zhihu.get_avatar_src(content)
    dbhelper.update_user_by_name(username, {'avatar': src})

    link_list = zhihu.get_answer_link_list(content)
    rs = zhihu.saveAnswer(conn, username, link_list, dblock)

    num = zhihu.get_page_num(content)
    if num > 1:
        for i in range(2, num):
            content = zhihu.fetch_people_page(conn, username, i)
            if content is None:
                print('content is None on line 34')
                continue
            link_list = zhihu.get_answer_link_list(content)
            zhihu.saveAnswer(conn, username, link_list, dblock)
    dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_OK})
    conn.close()
    zhihu.slog('### after saveAnswer ###')

if len(sys.argv) > 1:
    username = sys.argv[1]
    dbhelper.insert_user({'name': username, 'fetch': dbhelper.FETCH_ING})
    fetch_user(username)
else:
    threads = []
    while True:
        username = dbhelper.getNotFetchedUserName()
        if username is None:
            print('finish')
            break
        fetch_user(username)
    print('Complete')
