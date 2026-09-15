# Workflows

Agent loops are probabilistic. Business workflows should be deterministic where correctness matters.

A workflow is a DAG of named steps with dependencies. Steps may be actions, verification, approvals, or human gates.

```php
return (new WorkflowDefinition('refund-order'))
    ->step('validate', ValidateRefund::class)
    ->approval('finance', ['validate'])
    ->step('refund', RefundProvider::class, ['finance'], compensation: ReconcileRefund::class)
    ->verify('verify', VerifyRefund::class, ['refund']);
```

Each action/verify handler implements `WorkflowStepHandler`.

The runtime persists workflow runs/steps. Human/approval gates return `Waiting`; resume must use the same tenant and waiting step. Cancellation is tenant-scoped.

Compensation is best-effort. It is not a distributed transaction and cannot undo every external side effect. Ambiguous external outcomes require reconciliation.
