<?php
/**
 * AJAX response for sport form data
 *
 * @var \App\View\AppView $this
 * @var mixed $eavTemplate
 * @var mixed $error
 * @var mixed $sportConfigs
 * @var mixed $sportId
 * @var mixed $sportName
 * @var mixed $success
 */
echo json_encode(compact(
    'success',
    'sportId',
    'sportName',
    'sportConfigs',
    'eavTemplate',
    'error',
));
