<?php

    namespace App;

    trait Utils
    {
        public static function iterator ($array)
        {
            foreach ($array as $elmt)
            {
                yield $elmt;
            }
        }
    }
?>