<?php
	$GALMATRIX = array(
		array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
		array(0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
		array(0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0),
		array(0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0),
		array(0, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0),
		array(0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0),
		array(0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0),
		array(0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0),
		array(0, 0, 1, 1, 1, 1, 0, 1, 1, 1, 1, 0, 0),
		array(0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0),
		array(0, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0),
		array(0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0),
		array(1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1)
	 );

	 function getMatrix($rest) {
	 	$matrix = $GLOBALS['GALMATRIX'];
	 	$size = isset($GLOBALS['ELEMSINROW']) ? $GLOBALS['ELEMSINROW'] : 7;
	 	$divider = count($matrix[0]);
	 	$matrix = $matrix[$rest];

	 	if ($size >= $divider) {
	 		return $matrix;
	 	}

	 	$rem = ($divider - $size) / 2;
		for ($i = 0; $i < $rem; $i++) {
			array_shift($matrix);
			array_pop($matrix);
		}

		return $matrix;
	 }

	 function getElemsInRow() {
	 	$matrix = $GLOBALS['GALMATRIX'];
	 	$size = isset($GLOBALS['ELEMSINROW']) ? $GLOBALS['ELEMSINROW'] : 7;
	 	$divider = count($matrix[0]);

	 	if ($size > $divider) {
	 		$size = $divider;
	 	}

	 	return $size;
	 }
?>