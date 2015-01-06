import re
from urllib.parse import urlparse
import http.client
import dom

queue = [('www.hao123.com', '/')]
regex = re.compile(r';charset=([\w-]+)')

while len(queue) > 0:
    host, url = queue[0]
    print(host, url)
    conn = http.client.HTTPConnection(host, timeout=7)
    conn.request("GET", url)
    response = conn.getresponse()
    code = response.status
    print(host, url, code)
    if code != 200:
        print('code', code)
        queue = queue[1:]
        continue
    headers = dict(response.getheaders())
    print(headers)
    if 'Content-type' not in headers:
        print('no Content-type')
        queue = queue[1:]
        continue
    Ct = headers['Content-type']
    if Ct.find('text/html') != 0:
        print('not html')
        queue = queue[1:]
        continue
    ec = 'utf-8'
    m = regex.search(Ct)
    if m is not None:
        ec = m.group(1)
    content = response.read()
    doc = dom.bytes2dom(content, ec)
    node_list = doc.root.iter('a')
    for e in node_list:
        # print(e.get('href'))
        href = e.get('href')
        if href == '#':
            continue
        if href.find('javascript:') == 0:
            continue
        if href.find('http://') == 0:
            o = urlparse(href)
            print(href,o)
            queue.append((o.netloc, o.path+'?'+o.query))
    queue = queue[1:]
