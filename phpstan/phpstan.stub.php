<?php

// PHPStan stub to allow named-argument usage for CakePHP Table::get()
// This file provides an alternate signature declaration used by PHPStan's
// autoloading when scanning the project. It avoids adding phpdoc noise
// to model files.

namespace Cake\ORM {
    if (!class_exists('\Cake\\ORM\\Table')) {
        abstract class Table
        {
            /**
             * @param mixed $primaryKey
             * @param array<string>|array<string, mixed>|null $contain
             * @return \Cake\Datasource\EntityInterface|object|null
             */
            public function get($primaryKey, $contain = null) {}
        }
    }
}
