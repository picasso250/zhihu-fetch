#coding: utf-8

import sys, threading
import http.client
import zhihu
import dbhelper
import timer
import fetch
import asyncio

count = dbhelper.getNotFetchedUserCount()
print("there are", count, "user to fetch")

def fetch_user(username):
    dbhelper.update_user_by_name(username, {'fetch': dbhelper.FETCH_ING})
    content = fetch.people_page(username)

if len(sys.argv) > 1:
    username = sys.argv[1]
    dbhelper.insert_user({'name': username, 'fetch': dbhelper.FETCH_ING})
    fetch_user(username)
else:
    while True:
        username = dbhelper.getNotFetchedUserName()
        if username is None:
            print('finish')
            break
        fetch_user(username)
        coroutine.cycle()
coroutine.loop()
print('Complete')
