<?php

class HttpStatusCode
{
    // The request was successful
    const SUCCESS = 200;

    // The client send something wrong
    const BAD_REQUEST = 400;

    // There was an authorization problem
    const UNAUTHORIZED = 401;

    // The requested action wasn't available
    const NOT_FOUND = 404;

    // There was a server problem
    const INTERNAL_SERVER_ERROR = 500;
}