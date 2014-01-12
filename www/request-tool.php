<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
include('../start.php');
?>
<!doctype html>
<html>
    <head>
        <title>pocket-knife: console</title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/styles/reset.css" media="all" type="text/css" rel="stylesheet" />
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/styles/base.css" media="all" type="text/css" rel="stylesheet" />
        <style type="text/css">
            label{
                font-family: 'Arial', 'Helvetica', 'Verdana', 'Tahoma', sans-serif;
                font-weight: bold;
            }
            div.request{
                margin-bottom: 1em;
            }
            label.major{
                margin-top: 0.5em;
                display: block;
                width: 100%;
            }
            input[type=text], textarea{
                width: 100%;
                background-color: #999;
                border: 1px solid #282626;
                padding: 0.25em;
                font-family: 'Arial', 'Helvetica', 'Verdana', 'Tahoma', sans-serif;
            }
            button, input[type=submit]{
                padding: 0.25em;
                font-family: 'Arial', 'Helvetica', 'Verdana', 'Tahoma', sans-serif;
            }
            textarea{
                height: 10em;
            }
            textarea#response-body{
                height: 20em;
            }
        </style>
        <!--<link href="/pocket-knife/www/styles/vertical-rhythm.css" media="all" type="text/css" rel="stylesheet" />-->
    </head>
    <body>

        <!-- TITLE -->
        <h1>pocket-knife: console</h1>
        <hr class="title"/>

        <!-- REQUEST -->
        <h3>Request</h3>
        <div class="request">
            <form>
                <!-- URL -->
                <label class="major" for="url">URL</label>
                <input type="text" name="url" value="<?php echo WebUrl::create('demo/index.php'); ?>" id="url" />
                <!-- METHOD -->
                <label class="major">METHOD</label>
                <input type="radio" name="method" value="GET" id="method-get"/> <label for="method-get">GET</label>
                <input type="radio" name="method" value="POST" id="method-post"/> <label for="method-post">POST</label>
                <input type="radio" name="method" value="PUT" id="method-put"/> <label for="method-put">PUT</label>
                <input type="radio" name="method" value="DELETE" id="method-delete"/> <label for="method-delete">DELETE</label>
                <input type="radio" name="method" value="OPTIONS" id="method-options"/> <label for="method-options">OPTIONS</label>
                <input type="radio" name="method" value="HEAD" id="method-head"/> <label for="method-head">HEAD</label>
                <!-- CONTENT-TYPE -->
                <label class="major" for="content-type">Accept MIME Type</label>
                <input type="text" name="content-type" value="application/json" id="content-type" />
                <!-- BODY -->
                <label class="major" for="request-body">HTTP BODY</label>
                <textarea name="body" id="request-body"></textarea>
                <!-- SUBMIT -->
                <input type="submit" value="Send HTTP Request" id="request-send"/>
            </form>
        </div>

        <hr class="title"/>

        <!-- RESPONSE -->
        <h3>Response</h3>
        <div class="response">
            <textarea name="body" id="response-body"></textarea>
            <div id="response-time"></div>
        </div>

        <!-- SCRIPT -->
        <script type="text/javascript">
            
            $('request-send').onclick = function(e){
                // stop bubbling
                if (!e) e = window.event;
                if (e.cancelBubble) e.cancelBubble = true;
                else e.stopPropagation();
                // action
                var callback = function(request){
                    // end timer
                    endTime = new Date();
                    var time = endTime.getTime() - startTime.getTime();
                    $('response-time').innerHTML = 'Time elapsed: ' + time + 'ms; estimated RPS: ' + Math.round(1000/time);
                    // display HTTP response
                    var text = request.getAllResponseHeaders();
                    text += "\n";
                    text += request.responseText;
                    $('response-body').value = text;
                    console.log(request);
                }
                // time AJAX request
                var startTime = new Date();
                var endTime = null;
                ajax($('url').value, radio('method'), $('content-type').value, $('request-body').value, callback);
                // return
                return false;
            }
            
            /**
             * Returns an element given an ID
             */
            function $(id){
                return document.getElementById(id);
            }
            
            /**
             * Returns a radio element value checked
             */
            function radio(name){
                var inputs = document.getElementsByName(name);
                for (var i=0; i < inputs.length; i++){
                    if( inputs[i].type == 'radio' && inputs[i].checked ){
                            return inputs[i].value;
                    }
                }
                return null;
            }
            
            /**
             * Modified from http://microajax.googlecode.com/svn/trunk/microajax.js;
             * Copyright (c) 2008 Stefan Lange-Hegermann
             */
            function ajax(url, method, contentType, contentBody, callbackFunction)
            {
                this.bindFunction = function (caller, object) {
                    return function() {
                        return caller.apply(object, [object]);
                    };
                };

                this.stateChange = function (object) {
                    if (this.request.readyState==4)
                        this.callbackFunction(this.request);
                };

                this.getRequest = function() {
                    if (window.ActiveXObject)
                        return new ActiveXObject('Microsoft.XMLHTTP');
                    else if (window.XMLHttpRequest)
                        return new XMLHttpRequest();
                    return false;
                };

                this.url = url;
                this.method = method;
                this.contentType = contentType;
                this.contentBody = contentBody;
                this.callbackFunction = callbackFunction;
                this.request = this.getRequest();
                console.log(this);
        
                if(this.request) {
                    var req = this.request;
                    req.onreadystatechange = this.bindFunction(this.stateChange, this);
                    // request
                    req.open(this.method, this.url, true);
                    req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    req.setRequestHeader('Accept', this.contentType);
                    req.setRequestHeader('Connection', 'close');
                    req.send(this.contentBody);
                    console.log(req);
                }
            }
        </script>
    </body>
</html>