
table = {}

def go(*args, **kwargs):
    func = args[0]
    args = args[1:]
    key = str(func)+str(args)+str(kwargs)
    table[key] = (func, args, kwargs)
    cycle()

def cycle():
    pass

def sleep(time=0):
    on = True
