# Project Overview

## Purpose

This project is the **backend API** of a Human Resource Information System (HRIS) built with **Laravel 12**. It powers a headless architecture, serving a separate frontend application via a RESTful JSON API.

The system is designed to manage the complete employee lifecycle within a company — from onboarding and attendance tracking to leave management, payroll processing, and approval workflows.

---

## Main Features

| Feature | Description |
|---|---|
| **Authentication** | Token-based auth using Laravel Sanctum with email verification and password reset flows |
| **User & Role Management** | Role-based access control (RBAC) powered by Spatie Permission |
| **Employee Management** | Full employee profiles with position, division, team, and manager relationships |
| **Attendance** | Daily clock-in/clock-out, biometric sync, and geo-fencing support |
| **Leave Management** | Leave requests, early leave, leave type configuration, and balance tracking |
| **Attendance Requests & Overtime** | Employee-submitted corrections and overtime requests with approval flows |
| **Payroll** | Payroll generation, finalization, void handling, and downloadable payslips |
| **Notifications** | In-app notification system with mark-as-read and bulk-delete support |
| **Settings** | Configurable attendance rules and geo-fencing parameters |
| **Dashboard** | Separate dashboards for Admin and Employee roles |

---

## System Goals

- Provide a **clean, secure JSON API** for the HRIS frontend application
- Enforce **role-based permissions** across all modules
- Support configurable **attendance and schedule policies**
- Enable management of **multi-level organizational structures** (company → division → team → employee)
- Allow **export of key data** (attendance, payroll, shifts) to Excel and PDF

---

## High-Level Platform Description

The platform follows a **headless architecture** where this Laravel backend acts solely as an API server. The frontend (a separate application) consumes the API over HTTP using bearer tokens issued by Laravel Sanctum.

The backend manages:

- **Organizational hierarchy**: Divisions → Teams → Positions → Employees
- **Scheduling**: Work schedules, shift templates, and per-employee shift assignments
- **Time & Attendance**: Clock-in/out records from biometric devices or manual entry
- **HR Workflows**: Leave requests, early leave, attendance corrections, overtime — all routed through configurable approval chains
- **Payroll**: Computed from attendance data, allowances, and deductions; finalized and delivered as PDF payslips
