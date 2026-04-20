<?php

namespace  mnhcc\ml\interfaces;
{
	/**
	 * Interface for the MinimalusLayoutilus Exception.
	 * <p>
	 * Important, all exceptions should implement \JsonSerializable and mnhcc\ml\interfaces\MNHcC
	 * </p>
	 * @author Michael Hegenbarth (carschrotter)
	 * @package MinimalusLayoutilus	 
	 */
	interface Exception {
		const noMethodImplement = 7;
		const noStaticMethodImplement = 14;
	}
}