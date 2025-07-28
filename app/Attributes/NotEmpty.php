<?php
namespace App\Attributes;

use Attribute;

#[Attribute]
class NotEmpty {
    public function __construct(public string $message = "Value cannot be empty") {}
}
