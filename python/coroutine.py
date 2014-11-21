
def sender(f):
    def wrapper(*args, **kw):
        c = f(*args, **kw)
        next(c)
        return c
    return wrapper
pool = []
def executor(f):
    def wrapper(*args, **kw):
        c = f(*args, **kw)
        next(c)
        pool.append(c)
        return c
    return wrapper
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
