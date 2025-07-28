<?php
namespace App\Attributes;

use Attribute;

#[Attribute]
class NonNegative {
    public function __construct(public string $message = "Value cannot be negative") {}
}
