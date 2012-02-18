<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * File representation of a RESTful resource.
 * @uses Representation, ExceptionSettings, ExceptionWeb
 */
class RepresentationUpload extends RepresentationFile {

    /**
     * @see Representation::receive()
     */
    public function receive() {
        // grab first POST uploaded file
        $upload = reset($_FILES);
        if (!$upload)
            throw new ExceptionWeb("No uploaded file could be found", 404);
        if ($upload['error'])
            throw new ExceptionWeb("Upload failed: " . $upload['error'], 400);
        // get name
        $this->setName($upload['name']);
        // get data
        $this->setData(file_get_contents($upload['tmp_name']));
    }

}