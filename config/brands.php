<?php

// Extra spellings vendors use, keyed by the catalog brand name. Normalised on use
// (uppercase, accents removed, no spaces or punctuation). Add to this as files arrive.
return [
    'aliases' => [
        'MANN-FILTER' => ['MANN', 'MANN FILTER'],
        'FEBI BILSTEIN' => ['FEBI', 'BILSTEIN FEBI'],
        'KYB' => ['KAYABA'],
        'LEMFÖRDER' => ['LEMFORDER', 'LEMF'],
        'SACHS' => ['ZF SACHS'],
        'TRW' => ['TRW AUTOMOTIVE', 'LUCAS TRW'],
        'BOSCH' => ['ROBERT BOSCH'],
        'CONTINENTAL CTAM' => ['CONTITECH', 'CONTINENTAL'],
        'HELLA' => ['HELLA PAGID', 'HELLAPAGID'],
        'VALEO' => [],
        'NGK' => ['NGK SPARK PLUG'],
    ],
    // Ranking tier per brand name (premium | standard | budget). Default: standard.
    'tiers' => [
        'BOSCH' => 'premium', 'BREMBO' => 'premium', 'TRW' => 'premium', 'ATE' => 'premium',
        'MANN-FILTER' => 'premium', 'SACHS' => 'premium', 'LEMFÖRDER' => 'premium', 'NGK' => 'premium',
        'FEBI BILSTEIN' => 'standard', 'VALEO' => 'standard', 'HELLA' => 'standard',
    ],
];
