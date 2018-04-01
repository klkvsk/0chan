<?php

class ConditionalSwitch implements MappableObject {

	/**
	 * @var array(array(expression, value), ..)
	 */
	protected $cases = array();

	/** @var DialectString */
	protected $default = null;

	function __construct() {
	}

	public function toDialectString(Dialect $dialect) {
		$sql = 'CASE ';
		foreach ($this->cases as $case) {
			/** @var $logic DialectString */
			$logic = $case[0];
			/** @var $value DialectString */
			$value = $case[1];

			$sql .= 'WHEN ' . $logic->toDialectString($dialect)
				 . ' THEN ' . $value->toDialectString($dialect)
				 . ' ';
		}

		if ($this->default != null) {
			$sql .= ' ELSE ' . $this->default->toDialectString($dialect) . ' ';
		}

		$sql .= 'END';
		return '(' . $sql . ')';
	}

	public static function create() {
		return new self;
	}

    /**
     * @param LogicalObject $logic
     * @param $value
     * @return self
     */
	public function addWhen(LogicalObject $logic, $value) {
		$this->cases[] = array($logic, $value);
		return $this;
	}

    /**
     * @param $value
     * @return self
     */
	public function addElse($value) {
		$this->default = $value;
		return $this;
	}

	/**
	 * @param ProtoDAO $dao
	 * @param JoinCapableQuery $query
	 * @return ConditionalSwitch
	 */
	public function toMapped(ProtoDAO $dao, JoinCapableQuery $query) {
		$mapped = new self;
		foreach ($this->cases as $case) {
			$mapped->addWhen(
				$case[0]->toMapped($dao, $query),
				$dao->guessAtom($case[1], $query));
		}
		if ($this->default !== null) {
			$mapped->addElse($dao->guessAtom($this->default, $query));
		}
		return $mapped;
	}


}
