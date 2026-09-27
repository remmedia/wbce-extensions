<?php
final class WbceAuthenticationRegistry { private static array $providers=[]; public static function register(string $id,array $definition):void {self::$providers[$id]=$definition+['id'=>$id,'name'=>$id,'description'=>'','icon'=>'fa-key'];} public static function providers():array{return self::$providers;} }
