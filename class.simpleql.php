<?php

/*
Clase creada por Luis Macayo
2012
luismacayo.blogspot.com
http://code.google.com/p/changosql/
Actualizacion agosto 2013
metodos de obtencion de arreglos optimizados para la actualizacion php 5.3
Actualizacion diciembre 2013
Creacion del objeto mysqli y destruccion despues de la consulta
Actualizacion mayo 2014
Metodos adheridos manipulaciones de tablas creaTable inserTable updaTable deleTable
Optimizacion de creacion de querys utilizando la funcion implode
*/

require 'config.simpleql.php';

class Mysql {
	
	function __construct(){
		$this->servidor = SERVIDOR_BD; /* el servidor de tu base de datos, si la tienes local es "localhost"*/
		$this->usuario = USUARIO_BD; /* el usuario autorizado a la base de datos*/
		$this->password = PASSWORD_BD; /* password del usuario autorizado */
		$this->basedatos = NOMBRE_BD; /* nombre de la base de datos */
		$this->fecha = new Fecha();
	}
	
	/*
	Metodos de Consulta
	Este es el metodo medular de la clase, el realiza la consulta con la base de datos
	Crea el objeto con conectar
	Realiza la consulta y la guarda en la variable de ambito local result
	guarda el ultimo id de campo autoincrement
	guarda el codigo de erro, esta parte no esta bien todavia >D
	Desconecta de la base de datos
	devuelve el resultado de la consulta
	*/
	private function consulta($query){
		$this->conectar();
		$result = $this->mysqli->query(	$query	);
		$this->ultimoID = $this->mysqli->insert_id;
		$this->error = $this->mysqli->sqlstate;
		$this->desconectar();
	return $result;
	}
	
	private function fetchResult($result,$object = false,$utf8 = false,$params = array()){
		if(!$result) return array();
		if($object) for ($arreglo = array(); $tmp = $result->fetch_object(($object === true)? 'stdClass' : $object,$params); ) $arreglo[] = $tmp;
			else for ($arreglo = array(); $tmp = $result->fetch_array(MYSQLI_ASSOC);) $arreglo[] = ($utf8)? array_map('utf8_encode',$tmp) : $tmp;
		$result->free(); //Libera memoria
	return $arreglo;
	}
	
	/*funcion que devuelve una cadena escapada para insertar en un campo de una tabla normalizada*/
	private function escape($cadena){
		$this->conectar();
		$cadena_escapada = $this->mysqli->real_escape_string($cadena);
		$this->desconectar();
	return $cadena_escapada;
	}
	
	/*No funcional*/
	private function getError(){return $this->error;}
	
	/*funcion que utiliza inserTable que devuelve de la fila autoincrement de una tabla*/
	private function getID(){
		return $this->ultimoID;
	}
	
	/*crea el objeto mysqli que conecta con la base de datos*/
	private function conectar(){
		$this->mysqli = new mysqli($this->servidor,$this->usuario,$this->password,$this->basedatos);
	}
	
	/*destruye el objeto mysqli, liberando memoria y espacios para conectarse a la base de datos >D*/
	private function desconectar(){
		$this->mysqli->close();
	}
	
	public function select($select = array(),$from = array(),$where = array(),$group = array(),$order = array(),$limit = array(0,1000)){
		$query = "SELECT " . ((empty($select))?  "*" : implode(",",$select));
		if(!empty($from)) $query .= " FROM " . implode(",",$from);
		if(!empty($where)) $query .= " WHERE " . implode(" AND ",$where);
		if(!empty($group))	$query .= " GROUP BY " . implode(",",$group);
		if(!empty($order))	$query .= " ORDER BY " . implode(",",$order);
		if(!empty($limit))	$query .= " LIMIT " . implode(",",$limit);
	return $this->fetchResult(	$this->consulta($query), false, true	);
	}
	
	public function selectObject($select = array(),$from = array(),$where = array(),$class = 'stdClass',$params = array()){
		$query = "SELECT " . ((empty($select))?  "*" : implode(",",$select));
		if(!empty($from)) $query .= " FROM " . implode(",",$from);
		if(!empty($where)) $query .= " WHERE " . implode(" AND ",$where);
	return $this->fetchResult(	$this->consulta($query), $class, false, $params	);
	}
	
	/*Metodos de obtencion de datos de Tablas*/
	
	/*
	selecTable
	ARG
	table nombre de la tabla a insertar valores
	where array('nombre_columna' => 'valor_a_buscar')
	rev 2/6/2014
	rev 25/4/2015 agregado el limit
	*/
	public function selecTable($table,$select = array(),$where = array(),$operator = "AND",$object = false,$limit = 0){
		$query = "SELECT ";
		$query .= (empty($select))?  "*" : implode(",",$select);
		$query .= " FROM {$table}";
		if(!empty($where)) $query .= " WHERE " . implode(" AND ",$where);
		//if($limit > 0)$query .= " LIMIT 0,{$limit}";
		$this->inserTable('mysql_querys',array('query' => $query,'fecha' => $this->fecha->dateTime()));
	return $this->fetchResult(	$this->consulta($query)	);
	}
	
	/*Devuelve un array multidimensional de cada fila de la tabla*/
	public function arreglo($tabla,$where = array(),$operator = "AND",$comp = "="){
		$query = "SELECT * FROM {$tabla}";
		if(!empty($where)){
			$query .= " WHERE";
			foreach($where as $clave => $campo){
				$query .= " {$clave} {$comp} '{$campo}'";
				$query .= " {$operator}";				
			}
			$query = substr($query,0,strrpos($query,$operator));
		}
		return $this->fetchResult(	$this->consulta($query)	);
	}
	
	/*Devuelve un array multidimensional de cada fila de la tabla*/
	public function arregloFrom($tabla,$select = array(),$where = array(),$comp = "=",$operator = "AND",$limit = array(0,1000)){
		$query = "SELECT ";
		$query .=  empty($select)? '*' : implode(",",$select);
		$query .= " FROM {$tabla}";
		if(!empty($where)){
			$query .= " WHERE";
			foreach($where as $clave => $campo){
				$query .= " {$clave} {$comp} '{$campo}'";
				$query .= " {$operator}";				
			}
			$query = substr($query,0,strrpos($query,$operator));
		}
		if(count($limit) != 0) $query .= " LIMIT " . $limit[0] . "," . $limit[1];
	return $this->fetchResult(	$this->consulta($query), false, true	);
	}
	
	/*Devuelve un array multidimensional de cada fila de la tabla*/
	public function getFrom($tabla,$where = array(),$operator = "AND",$object = false,$order = array(),$limit = array(0,1000)){
		$query = "SELECT * FROM {$tabla}";
		if(!empty($where)){
			$query .= " WHERE";
			foreach($where as $campo => $value){
				$query .= " {$campo}='{$value}'";
				$query .= " {$operator}";				
			}
			$query = substr($query,0,strrpos($query,$operator));
			if(count($order) != 0) $query .= " ORDER BY " . $order[0] . " " . $order[1];
			if(count($limit) != 0) $query .= " LIMIT " . $limit[0] . "," . $limit[1];
		}
	return $this->fetchResult(	$this->consulta($query), false, true	);
	}
	
	/*Devuelve un array multidimensional de objetos stdClass de cada fila de la tabla*/
	public function objeto($tabla,$bandera = array(),$operator = "AND"){
		$query = "SELECT * FROM {$tabla}";
		if(!empty($bandera)){
			$query .= " WHERE";
			foreach($bandera as $clave => $campo){
				$query .= " {$clave}='{$campo}'";
				$query .= " {$operator}";				
			}
			$query = substr($query,0,strrpos($query,$operator));
		}
	return $this->fetchResult(	$this->consulta($query), true	);
	}
	
	public function objectClass($tabla,$class = 'stdClass'){
		$query = "SELECT * FROM {$tabla}";
		$result = $this->consulta($query);
		for($arreglo = array(); $tmp = $result->fetch_object($class); ) $arreglo[] = $tmp;
		$result->free(); //Libera memoria
	return $arreglo;		
	}
	
	/*Devuelve un array la fila de la tabla*/
	public function fila($tabla,$campos = array(),$operator = "AND"){
		$arreglo = $this->arreglo($tabla,$campos,$operator);
		if(count($arreglo) > 1) die("La fila tiene mas de un arreglo en la consulta: {$query}");
	return (isset($arreglo[0]))? $arreglo[0] : null;
	}
	
	/*Devuelve un array la fila de la tabla*/
	public function filaObject($tabla,$campos = array(),$operator = "AND",$class = 'stdClass'){
		$arreglo = $this->objeto($tabla,$campos,$operator);
		if(count($arreglo) > 1) die("La fila tiene mas de un arreglo en la consulta: {$query}");
	return (isset($arreglo[0]))? $arreglo[0] : null;
	}
	
	/*Manipulacion de Tablas 10/05/2014*/
	
	/*
	ARGS
	name STRING nombre de la tabla
	fields ARRAY la clave es el tipo de campo y el valor el nombre
	unique ARRAY indica los field unicos/clave de la tabla
	*/
	public function creaTable($name,$fields,$unique = array(),$engine = "MyISAM",$charset = "latin1",$auto_increment = 0){
		$query = "CREATE TABLE IF NOT EXISTS `{$name}` (";		
		foreach($fields as $nombre => $tipo){		
			switch($tipo){
				case 0 : $tipo_sring = "int(10) NOT NULL auto_increment"; $unique[] = $nombre; break;
				case 1 : $tipo_sring = "int(10) NOT NULL"; break;
				case 2 : $tipo_sring = "varchar(250) default NULL"; break;
				case 3 : $tipo_sring = "date default NULL"; break;
				case 4 : $tipo_sring = "text NOT NULL"; break;
				case 5 : $tipo_sring = "text"; break;
				case 6 : $tipo_sring = "longblob NOT NULL"; break;
				case 7 : $tipo_sring = "datetime NOT NULL"; break;
				case 8 : $tipo_sring = "char(1) NOT NULL"; break;
				case 9 : $tipo_sring = "char(1) NOT NULL default '0'"; break;
				case 10 : $tipo_sring = "datetime default NULL"; break;
				default : $tipo_sring = "varchar(25) default NULL";
			}			
			$query .= "`{$nombre}` {$tipo_sring},";
		}
		$query .= "UNIQUE (`";
		$query .= implode ("`,`",$unique);
		$query .= "`)";
		$query .= ") ENGINE={$engine}  DEFAULT CHARSET={$charset} AUTO_INCREMENT={$auto_increment}";
	return $this->consulta($query);
	}
	
	/*
	inserTable
	ARG
	table nombre de la tabla a insertar valores
	$table nombre de la tabla
	$arreglo array('nombre_columna' => 'valor_a_insertar')
	optimizada 10/5/2014
	*/
	public function inserTable($table,$arreglo){
		$fields = array_keys($arreglo);
		foreach($arreglo as $value) $values[] = $this->escape($value);
		$query = "INSERT INTO `{$table}` (`" . implode("`,`",$fields) . "`) VALUES ('" . implode("','",$values) . "')";
	return ($this->consulta($query))? $this->getID() : false ;
	}
	
	/*
	updaTable
	ARG
	tabla nombre de la tabla
	campos array('nombre_columna' => 'valor_a_actualizar')
	bandera array('nombre_columna_a_buscar' => 'valor_a_buscar')
	operador AND/OR
	*/
	public function updaTable($tabla,$set = array(),$where = array(),$operator = "AND"){
		$query = "UPDATE {$tabla} SET ";
		$k = 0;
		//if(!empty($set)) $query .= " SET " . implode("=",$set); else return false;
		foreach($set as $key => $value){ 
			if($k++ > 0) $query .= ",";
			$query .= "{$key}='{$this->escape($value)}'"; 
		}
		$query .= " WHERE";
		foreach($where as $clave => $campo){
			$query .= " {$clave}='{$campo}'";
			$query .= " {$operator}";				
		}
		$query = substr($query,0,strrpos($query,$operator));
	return $this->consulta($query);
	}	
	
	/*
	deleTable
	ARG
	tabla nombre de la tabla a borrar
	where especifica la fila a eliminar
	*/
	public function deleTable($tabla,$where = array(),$operator = "AND"){
		$query = "DELETE FROM `{$tabla}`";
		if(count($where)>0){
			$query .= " WHERE";
			foreach($where as $clave => $campo){
				$query .= " {$clave}='{$campo}'";
				$query .= " {$operator}";				
			}
		$query = substr($query,0,strrpos($query,$operator));
		}
	return $this->result = $this->consulta($query);
	}
	
	/*
	counTable
	ARG
	tabla nombre de la tabla a borrar
	where especifica la fila a eliminar
	*/
	public function countTable($tabla,$where = array(),$operator = "AND"){
	$query = "SELECT * FROM `{$tabla}`";
		/*if(count($where)>0){
			$query .= " WHERE";
			foreach($where as $clave => $campo){
				$query .= " {$clave}='{$campo}'";
				$query .= " {$operator}";				
			}
		$query = substr($query,0,strrpos($query,$operator));
		}*/
		$this->result = $this->consulta($query);
	return $this->result->num_rows;
	}
	
	public function addField($tabla,$campo){
	return $this->result = $this->consulta("ALTER TABLE `{$tabla}` ADD `{$campo['name']}` {$campo['type']} {$campo['default']}");
	}
	
	public function delField($tabla,$nombre_campo){
	return $this->result = $this->consulta("ALTER TABLE `{$tabla}` DROP `{$nombre_campo}`");
	}
	
	public function delete($tabla,$campo,$bandera){
	return $this->result = $this->consulta("DELETE FROM $tabla WHERE $campo='$bandera'");	
	}
	
	public function getColumns($tabla){
		$result = $this->consulta("SHOW COLUMNS FROM ".$tabla);
		for($arreglo = array(); $tmp = $result->fetch_array(MYSQLI_ASSOC);)	$arreglo[] = $tmp;
		$result->close();
	return $arreglo;
	}
	
	public function getFields($tabla){
		$fields = $this->getColumns($tabla);
		foreach($fields as $field)	$arreglo[] = $field["Field"];
	return $arreglo;
	}
	

}

?>
