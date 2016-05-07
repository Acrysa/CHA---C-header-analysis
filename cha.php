<?php

#CHA:xfaltu01
/*
*   	Author: Marie Faltusova
*   	Name: Analysis of C header files
*	Script: cha.php
*	Description: This script analyse file or directory given by 
*		     input argument and outputs a XML file containing
*		     list of functions and their details.
*/

//------------------------------------------------------------------Function part-->

function help() 
 {

    	echo "	Welcome in help log.
	  This is php script for analysing C header files,
	  searching function declarations in it.  It accepts
	  theese arguments:
	  --help 		Prints a help message.
	  --input=fileordir     Input file od directory for analysis.
	  --output=filename     Output file in XML format or STDOUT, if missing.
	  --pretty-xml=k        Formating of XLM doc, k is number of spaces.
	  --no-inline 		Skips functions with 'inline' specificator.
	  --max-par=n		Searches only functions with <n parameters.
	  --no-duplicates 	Skips duplicate functions.
	  --remove-whitespace 	Replaces tabs, newlines or multiple spaces with one space.

	  Program returns various errors, with codes:
	  1			Argument error - wrong or forbidden combination.
	  2			Problem with input file.
	  3			Problem with output file.
	  
	  Enjoy^^
				\n";
 }

function file_analysis($in_path)
 {// This function analyse input file 
  // and grep out all matches into array.

	if (0 == filesize($in_path))
	 {// file is empty    		
		fwrite(STDERR,"File is empty!\n");
		return 0;
	 }

	$h_file=file_get_contents($in_path);

	// Replace unwanted parts: comments, macros and strings
	$h_file = preg_replace("/\/\*.*?\*\//s", "", $h_file);  
 	$h_file = preg_replace("/\/\/.*?\n/", "", $h_file); 
 	$h_file = preg_replace("/#.*?\n/", "", $h_file);  
	$h_file = preg_replace("/\".*?\"/", "", $h_file);
		
	// Grep function declarations and definitions
	$matches=preg_match_all("/[a-zA-Z_][[:alpha:][:space:]]*?[[:graph:]]+?[[:space:]]+?[[:graph:]]+?[[:space:]]*?\([[:graph:][:space:]]*?\)[[:space:]]*?[;{]/", $h_file, $functions);
	
	if($matches==0) 
	    return 0;
	 else 
	    return $functions;
	
 }

function writeXML($XMLdoc,$out_file,$func,$file,$inline,$max_par,$duplicates,$replace_ws)
 { // This function create a function 
   // and param tag part of XML output file.
	
	$dupl=array();
	
	foreach($func[0] as $num => $fun_value)
	 {				
		preg_match("/([a-zA-Z_][[:alpha:][:space:]]*?[[:graph:]]+?)[[:space:]]*?([[:alnum:]_]+?)[[:space:]]*?\(/", $fun_value, $name);
		
		//Inline parameter		
		if(preg_match("/inline/", $name[1]) != 0 && !$inline) continue;	

		//Max-par parameter
		if($max_par!=false && preg_match_all("/[[:alnum:][:space:]*_\[\]]+[),]/", $fun_value, 							$params)>$max_par) continue;

		//No-duplicates parameter
		if(!$duplicates)
		 {
			if(in_array($name[2],$dupl)) continue;

			$dupl[$num]=$name[2];			
		 }
		
		//Remove-whitespace parameter (rettype)
		if($replace_ws)
		 {	
			$name[1]=preg_replace("/[[:space:]]+/"," ",$name[1]);
			$name[1]=preg_replace("/[[:space:]]*\*[[:space:]]*/","*",$name[1]);
		 }		 

		if(preg_match("/\,[[:space:]]*?\.\.\.\)/", $fun_value)) $varargs="yes";
		  else $varargs="no";	
		
 		//Function element
		$XMLdoc->startElement('function');
	  	  $XMLdoc->writeAttribute('file', $file);
	  	  $XMLdoc->writeAttribute('name', $name[2]);
	  	  $XMLdoc->writeAttribute('varargs', $varargs); 
	  	  $XMLdoc->writeAttribute('rettype', $name[1]);

 		//Parameters element
			if(preg_match("/\([[:space:]]*void[[:space:]]*+\)/", $fun_value,$params) == 0) 
			 {			 
				preg_match_all("/[[:alnum:][:space:]*_\[\]]+[),]/", $fun_value,$params);
				
				foreach($params[0] as $number => $par_value)
		      		 {	
					$par_value=preg_replace("/^[[:space:]]/","",$par_value);
					$cnt=preg_match_all("/[[:alnum:]_]+/", $par_value, $dummy);
					if( $cnt==1 || $cnt == preg_match_all("/auto|char|double|extern|float|int|long|register|short|static|typedef|union|unsigned|enum|void|const|volatile|signed/", $par_value, $dummy))
					 
						$type=preg_replace("/[[:space:]]*[),]/","",$par_value);					
					 
					 else
						$type=preg_replace("/[[:space:]]*[[:alnum:]_]*[[:space:]]*[),]/","",$par_value);
					 
					//Remove-whitespace parameter (type)
					if($replace_ws)	
					 {
						$type=preg_replace("/[[:space:]]+/"," ",$type);
						$type=preg_replace("/[[:space:]]*\*[[:space:]]*/","*",$type);
					 }

					 $XMLdoc->startElement('param');  
			   		 $XMLdoc->writeAttribute('number', $number+1);	
			   		 $XMLdoc->writeAttribute('type', $type);
					 $XMLdoc->endElement();//param
				 }
			 }//parameters
	
		$XMLdoc->endElement();//function

	 }//foreach functions		

 }

//<--Function part----------------------------------------------------Getopt part-->

 $longopts = array(
    
     "input:",
     "output:",
     "pretty-xml::",
     "no-inline",
     "max-par:",
     "no-duplicates",
     "remove-whitespace",
     "help",    
 );

 //Argument variables
 $in_path=getcwd();
    $dir="./";
 $out_file="php://output";
 $k=-1;
 $inline=true;
 $max_par=false;
 $duplicates=true;
 $replace_ws=false;
 $pXML=false;

 if($argc>1)
  {
	$options = getopt("false", $longopts);
	if($options == false || (count($options) != ($argc-1))) 
	 {
		fwrite(STDERR,"Invalid arguments!\n");
		exit(1);                     
	 }  	

	//Argument check out
	foreach($options as $opt => $opt_value)
	 {
		switch($opt) 
		 {
		    case 'help':
					if($argc != 2) 
					 {
					   	fwrite(STDERR,"Forbidden combination of arguments!\n");
					   	exit(1);                     
					 }
					  
					 help();
					 return 0;
			break;
		    case 'input':
			    		$in_path=$opt_value;
			break;
		    case 'output':												
					if(!(@$f=fopen($opt_value,'w'))) 
					 {
						fwrite(STDERR,"Opening an output file failed!\n"); 
						exit(3);
			 		 }

		 			 if(!is_dir($opt_value)) fclose($f);
					 $out_file=$opt_value;					
			break;
		    case 'pretty-xml':
					$pXML=true;					
					if($opt_value != "") 
					 {
						if(!is_numeric($opt_value) || intval($opt_value)<0)
						 {
							fwrite(STDERR,"Error args - pretty-xml has to be a number!\n"); 
						   	exit(1); 
					 	 }
					  	$k=$opt_value;						 
					 }                           
					 else   $k=4;				
			break;
		    case 'no-inline':
					$inline=false;				
			break;
		    case 'max-par':
					if(!is_numeric($opt_value)) 
					 {
						fwrite(STDERR,"Error args - max-par has to be a number!\n"); 
						exit(1); 
					 }

					$max_par=$opt_value;				 
			break;
		    case 'no-duplicates':
					$duplicates=false;
			break;
		    case 'remove-whitespace':
					$replace_ws=true;
			break;
		  }//switch
	
	 }//foreach argument
	
   }//else, if there are any args

//<--Getopt part---------------------------------------------------Write XML part-->

 // input check n' search	
 if(is_dir($in_path))
  {
	//Start XMLWriter
	$XMLdoc = new XMLWriter;		
	$XMLdoc->openMemory();		
	$XMLdoc->startDocument('1.0" encoding="UTF-8');

	if($k>=0)
	 {
		$s=str_repeat(" ",$k);
		$XMLdoc->setIndent(true);
		$XMLdoc->setIndentString($s);
	 }	

	$XMLdoc->startElement('functions');

	  if($in_path!=getcwd())
	   {
		$dir=$in_path;		 
		if(!preg_match("/\/$/", $dir))  
		   $dir .= '/';
	   }
	  $XMLdoc->writeAttribute('dir', $dir);
	
	  //Browse directory
	  $direct = new RecursiveDirectoryIterator($in_path);	
	  $extension = "h";
		
		foreach(new RecursiveIteratorIterator($direct) as $found_file)
		 {
	    		if (strcmp($extension, array_pop(explode('.', $found_file))) == 0)
			 {		
				if(file_analysis($found_file)!=0)
				 {//Match!				

					$func=file_analysis($found_file);
					
					if($in_path!=getcwd())
		 			  	$file=substr($found_file, strlen($dir)-strlen($found_file));	 			 
					 else					  
						$file=substr($found_file, strlen($in_path)-strlen($found_file)+1);	

					writeXML($XMLdoc,$out_file,$func,$file,$inline,$max_par,$duplicates,$replace_ws);	
				 }
				 //else No matches!				
			 }
		 }	
		
	//End XMLWriter's doc
	$XMLdoc->endElement();//functions	
	$XMLdoc->endDocument();
	
	$tmp=$XMLdoc->outputMemory();
	if(!$pXML) $tmp=preg_replace("/\n/","",$tmp);
 
	$XMLdoc->openURI($out_file);
	$XMLdoc->writeRaw($tmp);

  }// if is_dir
  else
   {//in_path is a file
	if(file_exists($in_path))
	 {//file!	
		
		//Start XMLWriter		
		$XMLdoc = new XMLWriter;
			
		$XMLdoc->openMemory();		
		$XMLdoc->startDocument('1.0', 'UTF-8');		

		if($k>=0)
		 {
			$s=str_repeat(" ",$k);
			$XMLdoc->setIndent(true);
			$XMLdoc->setIndentString($s);
		 }

		$XMLdoc->startElement('functions');
		 $XMLdoc->writeAttribute('dir', "");

		file_analysis($in_path);
			
		if(file_analysis($in_path)!=0)
		 {//Match!
			$func=file_analysis($in_path);
			$file=$in_path;
				
			writeXML($XMLdoc,$out_file,$func,$file,$inline,$max_par,$duplicates,$replace_ws);	
		 }
		 //else No matches!

		$XMLdoc->endElement();//functions	
		$XMLdoc->endDocument();
		
			$tmp=$XMLdoc->outputMemory();
			if(!$pXML) $tmp=preg_replace("/\n/","",$tmp);
 
			$XMLdoc->openURI($out_file);
			$XMLdoc->writeRaw($tmp);
	 	 
	 }
	 else
	  {									 
		fwrite(STDERR,"Input file does not exist!\n"); 
		exit(2);
	  }
		
   }//file

return 0;

?>
