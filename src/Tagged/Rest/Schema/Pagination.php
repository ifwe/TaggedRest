<?php
namespace Tagged\Rest\Schema;
use Tagged\Rest\Schema;

class Pagination extends Schema {
    function __construct($config){
        parent::__construct($config);
        $default = isset($config['default'])? $config['default'] : 25;
        $size = array(
            "description"=> "The number of items to fetch per page.",
            "default"=> $default,
            "minimum"=> 1,
        );

        $this->_schema = array(
            "type"=> "object",
            "description"=> "Request a slice of the final data set.",
            "properties"=> array(
                "page"=> array(
                    "type"=>"number",
                    "description"=> "The page",
                    "default"=> 1,
                    "minimum"=> 1,
                ),
                "size"=> $size,
            ),
            "required"=>array(
                "page",
                "size"
            )
        );
    }

    public static function offset($page, $size){
        return ($page - 1) * $size;
    }

    public static function getOffsetAndLimit($partial) {
        if(isset($partial->page)) {
            $offset = ($partial->page - 1) * $partial->size;
            return array($offset, $partial->size);
        }
    }
}
