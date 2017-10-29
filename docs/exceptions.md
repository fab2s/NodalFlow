# Exceptions


When an `Exception` is thrown during execution, NodalFlow catches it, perform few cleanup operations, including triggering the proper callback and then re-throws it as is if it's a `NodalFlowException` or else throws a `NodalFlowException` with the original exception set as previous. This means that NodalFlow will only throw `NodalFlowException` unless an exception is raised within a callback of yours.

`NodalFlowException` implement the informal and common context exception pattern (as in Symfony for example) to ease logging :
```php
    /**
     * @param string          $message
     * @param int             $code
     * @param null|\Exception $previous
     * @param array           $context
     */
    public function __construct($message, $code = 0, \Exception $previous = null, array $context = []);

    /**
     * @return array
     */
    public function getContext();
```

You can thus add context to exception when implementing nodes which will be easy to later retrieve and log (MonoLog/sentry etc ...).
