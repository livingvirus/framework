<?php
/*
$data=[
'test1'=>1,
'test2'=>2,
'test3'=>3,
]
$rule=[
'test1'=>['rules'=>require|(max,11)','errors'=>错误|最大不能超过11']
]
$Validation = new \think\Validation($data,$rule);
if(!$Validation->run()){
	echo $Validation->getError();
}
 */
namespace think;
class Validation {
	protected $_datas   = [];
	protected $_rules	= [];
	protected $_errors  = [];

	public function __construct( $datas = [], $rules = [], $Rule ='Rule')
	{	
		$this->_datas = $datas;
		$this->_rules = $rules;
		$class='\\think\\rules\\'.$Rule;
		$this->Rule = new $class;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Rules
	 */
	public function set_rules($rules = [])
	{
		if (!empty($rules))
		{
			$this->_rules[$field]  = $rules;
		}
		return $this;
	}

	/**
	 * Run the Validator
	 *
	 * This function does all the work.
	 * 
	 * @param	string	$group
	 * @return	bool
	 */
	public function run( )
	{
		if(!empty($this->_datas) && !empty($this->_rules))
		{
			foreach ($this->_datas as  $field=>$_data) 
			{
				//  验证规则| 分开或者
				$result=false;
				if(is_array($this->_rules[$field]))
				{ 
					foreach ($this->_rules[$field] as $_rule) 
					{
						if(method_exists($this->Rule, $_rule))
						{
							$result = $this->Rule->$_rule($_data);
						}
					}
				}elseif(is_string($this->_rules[$field]))
				{
					$rules = explode('|', $this->_rules[$field]);
					foreach ($rules as $rule) 
					{
						if(method_exists($this->Rule, $rule))
						{
							$result = $this->Rule->$rule($_data);
						}
					}
				}

				if(!$result)
				{
					$this->_errors[$field] = $this->_rules[$field]['errors'];
					return	false;
				}
			}
		}
		return true;
	}

	public function getError()
	{
		return $this->_errors;
	}
}
