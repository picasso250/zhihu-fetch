import asyncio
import datetime

@asyncio.coroutine
def bar():
    yield from asyncio.sleep(1)
    print('sleep 1')

@asyncio.coroutine
def foo():
    yield from asyncio.sleep(2)
    print('sleep 2')
loop = asyncio.get_event_loop()

tasks = [
    asyncio.async(foo()),
    asyncio.async(bar())]
loop.run_until_complete(asyncio.wait(tasks))

loop.close()
