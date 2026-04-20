<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes\Exception {

    use \mnhcc\ml,
	\mnhcc\ml\classes,
	\mnhcc\ml\traits,
	\mnhcc\ml\interfaces,
	\mnhcc\ml\classes\Exception;

    class ErrorException extends \ErrorException implements  interfaces\Exception{
	
	use traits\Exception;
//	use traits\MNHcC, 
//	    traits\NoInstances,
//	    traits\Exception{
//		traits\MNHcC::__toString insteadof traits\Exception;
//	    }

	public function __construct($message, $code, $severity, $filename, $lineno, $previous = null) {
	    parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
	}

    }

}