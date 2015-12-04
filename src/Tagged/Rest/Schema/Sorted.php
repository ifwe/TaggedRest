<?php
namespace Tagged\Rest\Schema;
use Tagged\Rest\Schema;

class Sorted extends Schema {
    function __construct(array $config){
        if(isset($config['default'])){
            $default = $config['default'];
        }
        else{
            $default = $config['types'][0];
        }

        $props = array(
            "type"=> array(
                "enum"=>$config['types'],
                "description"=> "How to sort the data",
                "default"=> $default,
            ),
            "order"=> array(
                "enum"=>array(
                    "asc",
                    "desc"
                ),
                "description"=>"Choose a direction to sort",
                "default"=>"asc"
            )
        );

        $this->_schema = array(
            "type"=> array(
                        "object",
                        "array"
            ),
            "items"=>array(
                "type"=>"object",
                "description"=>"A sort specification",
                "properties"=>$props
            ),
            "description"=> "Specify the sort type and direction. This can be a single sort specification, or an array of them",
            "additionalProperties"=>false,
            "properties"=> $props
        );
    }
}
