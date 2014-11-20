var http = require("http");
var fs = require("fs");

var options = {
  hostname: 'www.baidu.com',
  port: 80,
  path: '/',
  method: 'GET'
};

var req = http.request(options, function(res) {
  console.log('STATUS: ' + res.statusCode);
  console.log('HEADERS: ' + JSON.stringify(res.headers));
  res.setEncoding('utf8');
  var fd = fs.openSync('a.html', 'w');
  var offset = 0;
  res.on('data', function (chunk) {
    // console.log('BODY: ' + chunk);
    fs.write(fd, chunk, 0, chunk.length, null, function (err, written, buffer) {
      console.log(err);
      if (written !== chunk.length) {
        console.log('error, written '+written);
      };
    });
  });
});

req.on('error', function(e) {
  console.log('problem with request: ' + e.message);
});

// write data to request body
req.write('data\n');
req.write('data\n');
req.end();
