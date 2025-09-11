<?php

function money_to_float(?string $val): float {
    if(is_null($val)) return 0.0;
    return round((float) str_replace([' ', '.', 'R$', ','], ['', '', '', '.'], $val), 2);
}