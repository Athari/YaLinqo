<?php

require_once __DIR__ . '/../../YaLinqo/Linq.php';

function id ($o) { return spl_object_hash($o); }

function a () { return func_get_args(); }
