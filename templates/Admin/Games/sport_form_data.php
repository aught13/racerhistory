<?php
/**
 * AJAX response for sport form data
 */
echo json_encode(compact(
    'success',
    'sportId',
    'sportName',
    'sportConfigs',
    'eavTemplate',
    'error'
));
