# Budget API Documentation

## Budget Thresholds and Email Notifications

This API supports budget threshold monitoring and email notifications. When a budget's spending exceeds configured thresholds, notifications can be sent to specified email addresses.

### Budget Model Fields

The Budget model now includes the following fields related to notifications:

- **emails** (array of strings): Email addresses that will receive threshold notifications
- **thresholds** (array of integers): Percentage thresholds (1-99) that trigger notifications when exceeded
- **notification** (boolean): Enable/disable notifications for this budget

### Endpoints

#### Create Budget

**POST** `/{wsid}/budget`

Creates a new budget with optional threshold notifications.

**Request Body:**
```json
{
  "name": "Monthly Marketing Budget",
  "amount": 5000.00,
  "description": "Q4 marketing budget",
  "configuration": {
    "period": "monthly",
    "categories": [1, 2, 3],
    "wallets": [1],
    "tags": [],
    "types": ["expense"]
  },
  "notification": true,
  "emails": [
    "manager@example.com",
    "finance@example.com"
  ],
  "thresholds": [50, 75, 90]
}
```

**Response:** `201 Created`
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Monthly Marketing Budget",
  "amount": 5000.00,
  "description": "Q4 marketing budget",
  "configuration": { ... },
  "notification": true,
  "emails": ["manager@example.com", "finance@example.com"],
  "thresholds": [50, 75, 90],
  "workspace_id": 1
}
```

#### Update Budget

**PUT** `/{wsid}/budget/{uuid}`

Updates an existing budget, including threshold and email settings.

**Request Body:**
```json
{
  "name": "Monthly Marketing Budget",
  "amount": 6000.00,
  "description": "Updated Q4 marketing budget",
  "configuration": {
    "period": "monthly",
    "categories": [1, 2, 3],
    "wallets": [1],
    "tags": [],
    "types": ["expense"]
  },
  "notification": true,
  "emails": [
    "manager@example.com",
    "finance@example.com",
    "cfo@example.com"
  ],
  "thresholds": [40, 60, 80, 95]
}
```

**Response:** `200 OK`
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Monthly Marketing Budget",
  "amount": 6000.00,
  "description": "Updated Q4 marketing budget",
  "configuration": { ... },
  "notification": true,
  "emails": ["manager@example.com", "finance@example.com", "cfo@example.com"],
  "thresholds": [40, 60, 80, 95],
  "workspace_id": 1
}
```

#### Check Thresholds

**POST** `/{wsid}/budgets/check-thresholds`

Manually triggers threshold checking for all budgets in the workspace and sends notifications for exceeded thresholds.

**Response:** `200 OK`
```json
{
  "checked": true,
  "notifications_sent": 2,
  "details": [
    {
      "budget_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "budget_name": "Monthly Marketing Budget",
      "notified_thresholds": [50, 75],
      "current_percentage": 78
    },
    {
      "budget_uuid": "660e8400-e29b-41d4-a716-446655440001",
      "budget_name": "Travel Budget",
      "notified_thresholds": [90],
      "current_percentage": 92
    }
  ]
}
```

#### Get Budget

**GET** `/{wsid}/{uuid}`

Retrieves a specific budget including its threshold and email configuration.

**Response:** `200 OK`
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Monthly Marketing Budget",
  "amount": 5000.00,
  "description": "Q4 marketing budget",
  "configuration": { ... },
  "notification": true,
  "emails": ["manager@example.com", "finance@example.com"],
  "thresholds": [50, 75, 90],
  "workspace_id": 1
}
```

#### List Budgets

**GET** `/{wsid}`

Retrieves all budgets for a workspace, including threshold and email configuration.

**Response:** `200 OK`
```json
[
  {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Monthly Marketing Budget",
    "amount": 5000.00,
    "emails": ["manager@example.com"],
    "thresholds": [50, 75, 90],
    ...
  },
  {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Travel Budget",
    "amount": 3000.00,
    "emails": ["travel@example.com"],
    "thresholds": [80, 95],
    ...
  }
]
```

### Field Validation

#### Email Validation
- Emails must be valid email addresses
- Multiple email addresses can be specified
- Email field is optional (can be null or empty array)

**Valid examples:**
- `user@example.com`
- `user.name@example.com`
- `user+tag@domain.co.uk`

**Invalid examples:**
- `not-an-email`
- `@example.com`
- `user@`

#### Threshold Validation
- Thresholds must be integers
- Values must be between 1 and 99 (inclusive)
- Multiple thresholds can be specified
- Thresholds field is optional (can be null or empty array)
- Duplicates are allowed but may result in multiple notifications

**Valid examples:**
- `[50]`
- `[25, 50, 75]`
- `[10, 20, 30, 40, 50, 60, 70, 80, 90]`

**Invalid examples:**
- `[0]` - Below minimum
- `[100]` - Above maximum
- `[-10]` - Negative value
- `["fifty"]` - Non-numeric

### Notification Logic

When a budget's spending percentage exceeds one of the configured thresholds:

1. The system checks if notifications are enabled (`notification: true`)
2. The system verifies that email addresses are configured
3. For each exceeded threshold, a notification is sent to all configured email addresses
4. The notification includes:
   - Budget name
   - Total budget amount
   - Total spent
   - Remaining amount
   - Threshold percentage that was exceeded
   - Current spending percentage

**Example Notification:**

```
Subject: Budget Alert: 75% threshold exceeded

Budget 'Monthly Marketing Budget' has reached 75% of its allocated amount.

Budget Details:
- Total Budget: 5,000.00
- Total Spent: 3,850.00
- Remaining: 1,150.00
- Threshold: 75%
- Current Spending: 77%
```

### Use Cases

#### Example 1: Basic Threshold Monitoring

Create a budget with a single notification at 80%:

```json
{
  "name": "Office Supplies",
  "amount": 1000.00,
  "notification": true,
  "emails": ["manager@example.com"],
  "thresholds": [80],
  ...
}
```

#### Example 2: Multi-level Alerts

Create a budget with multiple thresholds for escalating awareness:

```json
{
  "name": "Annual Travel Budget",
  "amount": 50000.00,
  "notification": true,
  "emails": ["team-lead@example.com", "finance@example.com"],
  "thresholds": [50, 75, 90, 95],
  ...
}
```

- At 50%: Early warning to team lead
- At 75%: Mid-point check
- At 90%: Critical alert to finance team
- At 95%: Urgent notification

#### Example 3: No Notifications

Create a budget without notification features:

```json
{
  "name": "Internal Project",
  "amount": 2000.00,
  "notification": false,
  "emails": [],
  "thresholds": [],
  ...
}
```

### Best Practices

1. **Set Progressive Thresholds**: Use multiple thresholds (e.g., 50%, 75%, 90%) to provide early warnings
2. **Configure Multiple Recipients**: Add multiple email addresses for important budgets to ensure notifications reach the right people
3. **Test with Check Endpoint**: Use the `/budgets/check-thresholds` endpoint to manually test your notification setup
4. **Enable Notifications**: Remember to set `notification: true` to enable threshold checking
5. **Reasonable Thresholds**: Choose thresholds that give you enough time to take action (e.g., avoid only setting 99%)

### Error Handling

**400 Bad Request** - Validation errors:
```json
{
  "error": "Validation failed",
  "details": {
    "emails": "Invalid email address",
    "thresholds": "Threshold must be between 1 and 99"
  }
}
```

**404 Not Found** - Budget not found:
```json
{
  "message": "Budget not found"
}
```

**500 Internal Server Error** - Server error:
```json
{
  "error": "Internal server error",
  "message": "Failed to process request"
}
```
