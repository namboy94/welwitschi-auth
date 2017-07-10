<?php

namespace welwitschi;

/**
 * Initializes a Session
 */
function initializeSession() {
	if (session_status() === PHP_SESSION_NONE) {
		session_set_cookie_params(86400);
		session_start();
	}
}