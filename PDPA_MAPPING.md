# PDPA 2010 Compliance Mapping for TaskMaker Application

## Data Categorization

According to the Personal Data Protection Act 2010 (PDPA), the roles in our TaskMaker application can be categorized as follows:

| PDPA Category | TaskMaker Entity |
|---------------|------------------|
| Data User     | The TaskMaker application administrators |
| Data Subject  | Registered users of the application |
| Data Processor| The web hosting service providers |
| Personal Data | Name, email address, task details |
| Processing    | Collection, storing, and using of user data |

## PDPA Principles Mapping

### 1. General Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Consent must be obtained for processing personal data | Registration form with explicit consent checkbox | System Developer | RM500,000 fine or up to 3 years imprisonment or both |

Implementation details:
- Users must explicitly check the agreement checkbox during registration
- Modal dialog explains data usage in clear, simple language
- Consent is stored in the database (`data_retention_approved` field)

### 2. Notice and Choice Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Written notice about data collection must be provided | PDPA notice modal on registration page | System Developer | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- Clear notice explaining what data is collected
- Purpose of data collection is explained
- User rights under PDPA are listed

### 3. Disclosure Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Personal data cannot be disclosed without consent | Access control with authentication | System Administrator | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- Database access restricted to authenticated users
- Application uses separate database users with limited privileges
- Audit logging of all data access and modifications

### 4. Security Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Practical steps to protect personal data | Multiple security measures | System Administrator | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- Password hashing with strong algorithms
- Prepared statements to prevent SQL injection
- CSRF protection
- Input validation
- Brute force protection
- Session security enhancements
- Regular database backups

### 5. Retention Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Personal data shall not be kept longer than necessary | Data retention tracking | System Administrator | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- `data_retention_date` field tracks when consent was given
- Account inactivity tracking
- Notification system for inactive accounts before deletion

### 6. Data Integrity Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Data must be accurate, complete, not misleading | Input validation | System Developer | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- Form validation for input data
- User profile update capability
- Error handling for data integrity issues

### 7. Access Principle

| Requirement | Implementation | Responsible Personnel | Penalties for Non-compliance |
|-------------|----------------|------------------------|------------------------------|
| Data subject has right to access and correct personal data | User profile management | System Developer | RM300,000 fine or up to 2 years imprisonment or both |

Implementation details:
- Users can view their profile information
- Self-service password reset function
- Account deletion option for users

## Data Lifecycle Compliance

| Lifecycle Stage | PDPA Requirement | Implementation | Personnel |
|----------------|--------------------|----------------|-----------|
| Collection | Obtain explicit consent | Registration form with consent checkbox | System Developer |
| Storage | Secure storage and protection | Encrypted passwords, limited access | System Administrator |
| Usage | Use only for stated purposes | Task management functions | System Administrator |
| Maintenance | Keep data accurate and up-to-date | User profile update capability | System Administrator |
| Disposal | Delete when no longer needed | Account inactivity tracking | System Administrator |

## Security Measures for PDPA Compliance

1. **Technical Measures**:
   - Password hashing
   - Prepared SQL statements
   - Input validation
   - CSRF protection
   - Session security
   - Audit logging

2. **Administrative Measures**:
   - Clear data privacy policy
   - User rights information
   - Limited database privileges

3. **Physical Measures**:
   - Secure server infrastructure
   - Regular backups
   - Disaster recovery planning 