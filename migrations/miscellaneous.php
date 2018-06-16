<?php

namespace mafiascum\miscellaneous\migrations;

class miscellaneous extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {	
        return isset($this->config['mafiascum_miscellaneous']);;
    }
    
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }
    
	public function update_data()
    {
        return array(
			array('config.add', array('mafiascum_miscellaneous', 1)),
        );
    }
	
}
?>