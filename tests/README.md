# Test Suite Documentation

## Overview

This project includes comprehensive testing for the security layer, specifically focusing on the RecipeVoter implementation. The test suite validates that access control is properly enforced across different user roles and permissions.

## Test Status Summary

✅ **Unit Tests**: 21/21 passing (RecipeVoter security logic)  
✅ **Simple Controller Tests**: 1/1 passing (Route authentication)  
⚠️ **Functional Tests**: Require database setup for full execution  

## Test Structure

### Unit Tests (✅ Working)

#### `tests/Security/Voter/RecipeVoterTest.php`
- **Status**: ✅ All 21 tests passing
- **Purpose**: Comprehensive unit testing of RecipeVoter security logic
- **Coverage**: 
  - Role hierarchy validation (ROLE_STUDENT → ROLE_MODERATOR → ROLE_ADMIN)
  - All permissions (VIEW, CREATE, EDIT, DELETE)
  - Ownership validation
  - Anonymous user handling
  - Edge cases (recipes without authors, invalid subjects)

**Test Results:**
```
Recipe Voter (App\Tests\Security\Voter\RecipeVoter)
 ✔ Vote without user
 ✔ Vote with non user object
 ✔ Abstain for unsupported attribute
 ✔ Abstain for unsupported subject
 ✔ Admin has full access
 ✔ View access
 ✔ Create access for student
 ✔ Create access for moderator
 ✔ Create access denied for regular user
 ✔ Edit own recipe
 ✔ Edit others recipe as regular user
 ✔ Edit as moderator can edit all
 ✔ Delete own recipe
 ✔ Delete others recipe
 ✔ Delete as moderator only own
 ✔ Delete as moderator own recipe
 ✔ Recipe without author
 ✔ Role hierarchy no special roles
 ✔ Role hierarchy student role
 ✔ Role hierarchy moderator role
 ✔ Role hierarchy both roles

OK (21 tests, 28 assertions)
```

### Simple Controller Test (✅ Working)

#### `tests/Controller/RecipeControllerTest.php`
- **Status**: ✅ Working
- **Purpose**: Basic route testing without database dependencies
- **Test**: Verifies authentication redirects work properly

### Functional Tests (⚠️ Requires Database Setup)

#### `tests/Controller/RecipeControllerSecurityTest.php`
- **Status**: ⚠️ Requires database configuration
- **Purpose**: End-to-end testing of route-level security and controller access control
- **Issue**: Functional tests require actual database access to create test users and recipes

#### Database Configuration for Functional Tests

The functional tests require proper database setup. A SQLite test configuration has been created:

**`config/packages/test/doctrine.yaml`:**
```yaml
doctrine:
    dbal:
        # In-memory SQLite for testing
        driver: 'pdo_sqlite'
        url: 'sqlite:///:memory:'
        charset: 'UTF8'
```

**`tests/Controller/DatabaseTestCase.php`:** Base class for functional tests that sets up the database schema.

## Running Tests

### Run All Unit Tests (Recommended)
```bash
php bin/phpunit tests/Security/
```

### Run Specific Test Classes
```bash
# RecipeVoter unit tests (always working)
php bin/phpunit tests/Security/Voter/RecipeVoterTest.php

# Simple controller test (always working)
php bin/phpunit tests/Controller/RecipeControllerTest.php
```

### Run All Tests (May show database warnings)
```bash
php bin/phpunit --testdox
```

## Security Rules Validated

The tests confirm the following security rules are properly implemented:

### Role Hierarchy
- `ROLE_ADMIN` > `ROLE_MODERATOR` > `ROLE_STUDENT` > `ROLE_USER`

### Recipe Permissions

| Role | VIEW | CREATE | EDIT | DELETE |
|------|------|--------|------|--------|
| Anonymous | ❌ | ❌ | ❌ | ❌ |
| ROLE_USER | ✅ | ❌ | ❌ | ❌ |
| ROLE_STUDENT | ✅ | ✅ | Own only | Own only |
| ROLE_MODERATOR | ✅ | ✅ | All recipes | Own only |
| ROLE_ADMIN | ✅ | ✅ | All recipes | All recipes |

### Key Security Features Tested
- ✅ Ownership validation (users can only edit/delete their own recipes, except moderators who can edit all)
- ✅ Role-based access control (students can create, regular users cannot)
- ✅ Administrative privileges (admins have full access)
- ✅ Anonymous user blocking (all operations require authentication)
- ✅ Edge case handling (recipes without authors, invalid parameters)

## Recommendations

1. **Use Unit Tests for Development**: The RecipeVoter unit tests provide comprehensive coverage and run quickly
2. **Database Setup for Full Testing**: To run functional tests, ensure test database is properly configured
3. **Continuous Integration**: Unit tests can run in CI without database dependencies
4. **Security Validation**: All security rules are thoroughly tested and validated

## Test Commands Summary

```bash
# Quick security validation (unit tests only)
php bin/phpunit tests/Security/Voter/RecipeVoterTest.php

# All working tests
php bin/phpunit tests/Security/ tests/Controller/RecipeControllerTest.php

# Full test suite (may require database setup)
php bin/phpunit --testdox
```