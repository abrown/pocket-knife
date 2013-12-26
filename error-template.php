<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $resource->http_code; ?> <?php echo $resource->http_message; ?></title>
        <style type="text/css">
            html{
                background-color: #282626;
            }
            body{
                background-color: #ffffff;
                display: block;
                font-size: 16px;
                font-family: 'Georgia', 'Times New Roman', serif;
                line-height: 16px;
                margin: 2em auto 2em;
                max-width: 1200px;
                min-width: 600px;
                padding: 2em;
                width: 72%;
            }

            /** HORIZONTAL RULE **/
            hr{
                background-color: #D9CECE;
                border: 0;
                color: #D9CECE;
                height: 1px;
                margin: 0 -2em;
                padding: 0;
            }
            hr.title{
                margin-bottom: 40px;
            }

            /** TEXT **/
            h1, h2, h3, h4, h5, h6{
                font-family: 'Arial', 'Helvetica', 'Verdana', 'Tahoma', sans-serif;
                font-weight: bold;
            }
            h1{
                font-size: 3em;
                line-height: 1em;
            }
            p{
                margin-bottom: 0em;
            }
            span.property{
                font-style: italic;
            }
            pre{
                background-color: #D9CECE;
                color: black;
                padding: 1em;
                overflow-x: scroll;
                overflow-y: hidden;
            }
        </style>
    </head>
    <body>

        <!-- TITLE -->
        <h1><?php echo $resource->http_code; ?> <?php echo $resource->http_message; ?></h1>
        <hr class="title"/>
        <p><span class="property">Message</span>: <?php echo $resource->message; ?></p>
        <p><span class="property">Thrown at</span>: <?php echo $resource->file; ?>(<?php echo $resource->line; ?>)</p>
        <pre>Thrown at <?php echo $resource->file; ?>(<?php echo $resource->line; ?>)
<?php
foreach ($resource->trace as $line) {
    echo $line . "\n";
}
?></pre>
        <?php if($resource->uncaught){
            echo '<p class="alert">This error was not caught by a try-block; it originated at:</p>'."\n";
            echo '<pre>'.$resource->uncaught_from.'</pre>';
        } ?>        
    </body>
</html>