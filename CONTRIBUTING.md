# Contributing

1. Add tests for behavior changes, especially authorization, tenant isolation, approvals, idempotency and ambiguous operations.
2. Do not add generic arbitrary-SQL or arbitrary-shell agent tools to core.
3. Keep provider-specific behavior behind contracts/adapters.
4. Run `composer test`, `composer lint`, `composer analyse`, and `php scripts/smoke.php`.
5. Treat backward compatibility of persisted run/step records as a production concern.
