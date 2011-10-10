<h1>security</h1>
<?php
pr('Testing Basic Transport Authentication...');
assert('$auth = new Authentication("Basic");');
assert('$auth->setAcl("Array");');