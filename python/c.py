def Cor():
    i = 44
    yield
    print('on ',6)
    while True:
        print('on',5)
        y = yield i
        print('y =', y)
        i+=1
        print('on',4)

print('init')
c = Cor()
print('emtpy next')
next(c)
print('send 1')
print(c.send(1))
print('first next')
# print(next(c))
print('first send')
print(c.send(2))
print('second next')
# print(next(c))
print(c.send(3))
# print(next(c))
print(c.send(4))
# print(next(c))
