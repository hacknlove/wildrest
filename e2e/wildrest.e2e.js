var request = require('request'),
assert  = require('assert'),
diff = require('diff'),
util = require('util'),
colors = require('colors'),

E2E = function(request,title, parent){
  this.request = request;
  request._get_ = E2E.prototype._get_;
  this._title_ = title;
  if(parent){
    this._parent_ = parent;
    this.request._parent_ = parent.request;
  }
  this.callbacks = [];
  this.resolved = true;
};

E2E.prototype._get_ = function(key){
  if(typeof(this[key])!=='undefined'){
    return this[key];
  }
  if(this._parent_){
    return this._parent_._get_(key);
  }else{
    return null;
  }
};
E2E.prototype._gettitle_ =function(){
  if(this._parent_){
    return this._parent_._gettitle_() +':'+ (this._title_||'-');
  }
  return (this._title_||'-');
};

E2E.prototype._addCallback_ = function(callback){
  if(this.resolved){
    callback.call();
  }else{
    this.callbacks.push(callback);
  }
};
E2E.prototype._resolve_ = function(){
  this.resolved = true;
  this.callbacks.forEach(function(callback){
    callback.call();
  });
};

E2E.prototype.GET    = function(request, title){
  if(!title){
    title = 'GET';
  }
  return this.HTTP('GET', request, title);
};
E2E.prototype.POST   = function(request, title){
  if(!title){
    title = 'POST';
  }
  return this.HTTP('POST', request, title);
};
E2E.prototype.DELETE = function(request, title){
  if(!title){
    title = 'DELETE';
  }
  return this.HTTP('DELETE', request, title);
};
E2E.prototype.PUT    = function(request, title){
  if(!title){
    title = 'PUT';
  }
  return this.HTTP('PUT', request, title);
};

E2E.prototype.HTTP = function(method, req, title){

  if(!title){
    title = 'HTTP';
  }
  if(typeof(req) === 'undefined'){
    request = {};
  }
  req.method = method;
  var status = new E2E(req,title,this);
  status.resolved = false;
  
  this._addCallback_(function(){
 
    if(status._get_('e')){
      status._resolve_();
      return;
    }
    
    request({
      url             :(req._get_('domain'))+(req._get_('url') || '/'),
      qs              : req._get_('query')           || {},
      method          : method,
      headers         : {'e2e':true},
      json            : req._get_('json')            || {},
      followRedirect  : req._get_('followRedirect')  || false,
      timeout         : req._get_('timeout')         ||1000
    },function(e,r,body){
      status.e = e;
      status.r = r;
      status.body = body;
      status._resolve_();
      if(e){
        console.log(status._gettitle_().bold);
        console.log(util.inspect(e));
      }
    });
  });

  return status;

};

E2E.prototype.expect = function(expectation, title){
  if(!title){
    title = 'expect';
  }
  var status = new E2E({},title,this);
  status.resolved = false;
  
  this._addCallback_(function(){
    if(status._get_('e')){
      status._resolve_();
      return;
    }

    var r = status._get_('r'),
    body = status._get_('body'),
    errors = '';
    
    if(expectation.status && expectation.status!=r.statusCode){
      errors += ' status recibido: '.red+(r.statusCode+'').red + '\tstatus esperado: '.green.underline+(expectation.status+'').green.underline + '\n';
    }

    if(expectation.json){
      if(typeof body == 'string'){
        errors += "Not Json: \n".red+body;
      }else{
        var jsonbody = JSON.stringify(body, null, 4),
        jsonrespuesta = JSON.stringify(expectation.json, null, 4);
        if(jsonbody!=jsonrespuesta){
          var output = '',    
          jsondiff = diff.diffWords(jsonrespuesta, jsonbody);

          jsondiff.forEach(function(item){
            if(item.added){
              output = output+item.value.blue;
            }else if(item.removed){
              output = output+item.value.red;
            }else{
              output = output+item.value.green;
            }
          });
          errors += output+'\n';
        }
      }
    }

    if(errors){
      status.error(errors);
    }
    status._resolve_();
  });
  return status;
};
E2E.prototype.call = function(callback, title){
  if(!title){
    title = 'call';
  }

  var status = new E2E({},title,this);
  status.resolved = false;

  this._addCallback_(callback.bind(status));
  return status;
};
E2E.prototype.error = function(message){
  console.log(this._gettitle_().bold);
  console.log(message);
  this.e=true;
}
E2E.prototype.show = function(){
    util.inspect(this._get_('body'))
  return this;
}



module.exports.E2E = E2E;
