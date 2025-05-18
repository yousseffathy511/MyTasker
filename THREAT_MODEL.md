# TaskMaker Security Threat Model

This document outlines the threat modeling conducted for the TaskMaker application using the STRIDE and DREAD methodologies.

## STRIDE Threat Model

STRIDE is an acronym representing six threat categories:
- **S**poofing
- **T**ampering
- **R**epudiation
- **I**nformation disclosure
- **D**enial of service
- **E**levation of privilege

### 1. Spoofing (Authentication)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| Password guessing | - Strong password requirements<br>- Brute force protection<br>- Account lockout after multiple failed attempts | Medium |
| Session hijacking | - Secure session handling<br>- Session ID regeneration after login<br>- HTTPS recommended for production | High |
| Username enumeration | - Generic error messages for both invalid username and password<br>- Rate limiting on login attempts | Medium |

### 2. Tampering (Integrity)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| SQL injection | - Prepared statements for all database queries<br>- Input validation and sanitization<br>- Database user with limited privileges | High |
| Cross-site request forgery (CSRF) | - CSRF tokens on all forms<br>- Token validation on all state-changing actions | High |
| Parameter manipulation | - Server-side validation of all parameters<br>- Owner verification before task operations | Medium |

### 3. Repudiation (Non-repudiation)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| Unauthorized actions | - Comprehensive audit logging<br>- Logging of all authentication attempts<br>- Recording user actions with timestamps and IP addresses | Medium |
| Log tampering | - Limited access to log files<br>- Database-stored logs with restricted access | Medium |
| Account sharing | - Session timeout after inactivity<br>- Monitoring of unusual login patterns | Low |

### 4. Information Disclosure (Confidentiality)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| Sensitive data exposure | - Password hashing<br>- Database user with limited privileges<br>- Data separation between users | High |
| Error message information leakage | - Generic error messages<br>- Detailed errors logged but not displayed to users | Medium |
| Insecure transmission | - HTTPS implementation for production<br>- Secure cookie flags | High |

### 5. Denial of Service (Availability)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| Application resource exhaustion | - Input size limitations<br>- Transaction timeouts<br>- Rate limiting for API endpoints | Medium |
| Database connection exhaustion | - Connection pooling<br>- Query timeouts | Low |
| Large file uploads | - File size limits<br>- File type validation | Low |

### 6. Elevation of Privilege (Authorization)

| Threat | Implemented Controls | Risk Level |
|--------|----------------------|------------|
| Horizontal privilege escalation | - User-based task ownership validation<br>- Authorization checks on all endpoints | High |
| Vertical privilege escalation | - Separation of admin and user roles<br>- Principle of least privilege for database users | Medium |
| Missing function level access control | - Server-side authentication and authorization checks<br>- No reliance on hiding UI elements for security | High |

## DREAD Risk Assessment

DREAD is a risk assessment model used to calculate the severity of identified threats:
- **D**amage potential: How bad would an attack be?
- **R**eproducibility: How easy is it to reproduce the attack?
- **E**xploitability: How much work is it to launch the attack?
- **A**ffected users: How many users would be affected?
- **D**iscoverability: How easy is it to discover the threat?

Each factor is rated 1-3 (Low-High), and the sum gives an overall risk score (5-15).

### High-Risk Threats (Scored Using DREAD)

| Threat | D | R | E | A | D | Total | Risk |
|--------|---|---|---|---|---|-------|------|
| SQL Injection in task management | 3 | 3 | 2 | 3 | 2 | 13 | High |
| Session hijacking | 3 | 2 | 2 | 3 | 2 | 12 | High |
| Cross-site request forgery | 3 | 3 | 2 | 2 | 2 | 12 | High |
| Authentication bypass | 3 | 1 | 2 | 3 | 2 | 11 | High |
| Privilege escalation (accessing other users' tasks) | 2 | 2 | 2 | 2 | 2 | 10 | High |

### Medium-Risk Threats (Scored Using DREAD)

| Threat | D | R | E | A | D | Total | Risk |
|--------|---|---|---|---|---|-------|------|
| Brute force password attacks | 2 | 2 | 2 | 1 | 2 | 9 | Medium |
| Sensitive data exposure | 2 | 2 | 1 | 2 | 1 | 8 | Medium |
| Information disclosure via error messages | 1 | 2 | 2 | 2 | 1 | 8 | Medium |
| Denial of Service via large requests | 2 | 2 | 1 | 2 | 1 | 8 | Medium |
| Username enumeration | 1 | 2 | 2 | 1 | 1 | 7 | Medium |

### Low-Risk Threats (Scored Using DREAD)

| Threat | D | R | E | A | D | Total | Risk |
|--------|---|---|---|---|---|-------|------|
| Log tampering | 1 | 1 | 1 | 1 | 1 | 5 | Low |
| Account sharing | 1 | 1 | 1 | 1 | 1 | 5 | Low |
| Clickjacking | 1 | 2 | 1 | 1 | 1 | 6 | Low |


## Security Controls Implementation

Based on the threat modeling, the following security controls have been implemented in the TaskMaker application:

### Authentication & Authorization
1. Strong password requirements with complexity validation
2. Brute force protection with account lockout
3. Secure session management
4. User-based task ownership validation

### Input Validation & Data Protection
1. Prepared statements for all database queries
2. Input validation and sanitization
3. CSRF protection on all forms
4. Secure password hashing

### Logging & Monitoring
1. Comprehensive audit logging system
2. Login attempt tracking
3. User activity logging

### Database Security
1. Limited privileges for application database user
2. Data separation between users
3. Regular database backups

### Session Security
1. Session timeout after inactivity
2. Session ID regeneration after login
3. Secure cookie settings (when HTTPS is used)

## Conclusion

The threat modeling exercise has identified several potential security risks in the TaskMaker application. The implementation of security controls addresses these risks, with priority given to high-risk threats. Regular security assessments should be conducted to ensure these controls remain effective.
