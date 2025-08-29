<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Traits\ApiResponseTrait;
use Throwable;

class ApiErrorHandler
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse();
        } catch (AuthenticationException $e) {
            return $this->unauthorizedResponse();
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse();
        } catch (Throwable $e) {
            if (config('app.debug')) {
                throw $e;
            }
            return $this->serverErrorResponse();
        }
    }
}
