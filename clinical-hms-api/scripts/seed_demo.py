"""
Demo data seed script — development use only.

Creates a realistic starting state for demonstrating and testing the full
clinical workflow without having to manually create accounts via the API:

  1. Demo Community Clinic (Gauteng, Johannesburg)
  2. Five staff accounts — one per role — all with the same password.
  3. Three patients already checked in and waiting in the triage queue.

Run after migrations:
  docker compose exec api python -m scripts.seed_demo

The script is idempotent: running it twice does not create duplicate records.

WARNING: These accounts use a weak, publicly known password. They must never
be used in a production or customer-facing environment.
"""

import sys
from datetime import UTC, datetime, timedelta
from pathlib import Path

from sqlalchemy import select

sys.path.append(str(Path(__file__).resolve().parents[1]))

from app.core.security import hash_password
from app.db.models import Facility, Patient, User, Visit
from app.db.models.user import AccountType, StaffRole
from app.db.models.visit import VisitStatus
from app.db.session import SessionLocal


DEMO_PASSWORD = "Password123!"

# Development-only staff accounts used by the React auth shell and demos.
# Do not use these credentials for a real deployment.
DEMO_USERS = [
    {
        "first_name": "Amina",
        "surname": "Admin",
        "email": "admin@clinicdemo.co.za",
        "employee_number": "DEMO-ADMIN-001",
        "role": StaffRole.admin,
        "department": "Administration",
    },
    {
        "first_name": "Rachel",
        "surname": "Reception",
        "email": "reception@clinicdemo.co.za",
        "employee_number": "DEMO-RECEPTION-001",
        "role": StaffRole.reception,
        "department": "Reception",
    },
    {
        "first_name": "Nandi",
        "surname": "Nurse",
        "email": "nurse@clinicdemo.co.za",
        "employee_number": "DEMO-NURSE-001",
        "role": StaffRole.nurse,
        "department": "Nursing",
    },
    {
        "first_name": "Daniel",
        "surname": "Doctor",
        "email": "doctor@clinicdemo.co.za",
        "employee_number": "DEMO-DOCTOR-001",
        "role": StaffRole.doctor,
        "department": "Consultation",
    },
    {
        "first_name": "Priya",
        "surname": "Pharmacist",
        "email": "pharmacist@clinicdemo.co.za",
        "employee_number": "DEMO-PHARMACIST-001",
        "role": StaffRole.pharmacist,
        "department": "Pharmacy",
    },
]

DEMO_TRIAGE_PATIENTS = [
    {
        "first_name": "Thabo",
        "surname": "Mokoena",
        "folder_number": "F-1001",
        "reason_for_visit": "Fever and cough",
        "wait_minutes": 45,
    },
    {
        "first_name": "Lerato",
        "surname": "Nkosi",
        "folder_number": "F-1002",
        "reason_for_visit": "Abdominal pain",
        "wait_minutes": 28,
    },
    {
        "first_name": "Sipho",
        "surname": "Dlamini",
        "folder_number": "F-1003",
        "reason_for_visit": "Follow-up hypertension review",
        "wait_minutes": 12,
    },
]


def get_or_create_demo_facility() -> Facility:
    with SessionLocal() as db:
        facility = db.scalar(
            select(Facility).where(Facility.name == "Demo Community Clinic")
        )

        if facility is not None:
            return facility

        facility = Facility(
            province="Gauteng",
            city="Johannesburg",
            name="Demo Community Clinic",
        )
        db.add(facility)
        db.commit()
        db.refresh(facility)
        return facility


def seed_demo_users() -> None:
    facility = get_or_create_demo_facility()

    with SessionLocal() as db:
        for demo_user in DEMO_USERS:
            existing_user = db.scalar(
                select(User).where(User.email == demo_user["email"])
            )

            if existing_user is not None:
                continue

            db.add(
                User(
                    account_type=AccountType.staff,
                    first_name=demo_user["first_name"],
                    surname=demo_user["surname"],
                    email=demo_user["email"],
                    hashed_password=hash_password(DEMO_PASSWORD),
                    employee_number=demo_user["employee_number"],
                    role=demo_user["role"],
                    department=demo_user["department"],
                    facility_id=facility.id,
                )
            )

        db.commit()


def seed_demo_triage_queue() -> None:
    facility = get_or_create_demo_facility()
    now = datetime.now(UTC)

    with SessionLocal() as db:
        for demo_patient in DEMO_TRIAGE_PATIENTS:
            existing_patient = db.scalar(
                select(Patient).where(
                    Patient.folder_number == demo_patient["folder_number"],
                    Patient.facility_id == facility.id,
                )
            )

            if existing_patient is None:
                patient = Patient(
                    first_name=demo_patient["first_name"],
                    surname=demo_patient["surname"],
                    folder_number=demo_patient["folder_number"],
                    facility_id=facility.id,
                )
                db.add(patient)
                db.flush()
            else:
                patient = existing_patient

            existing_visit = db.scalar(
                select(Visit).where(
                    Visit.patient_id == patient.id,
                    Visit.status == VisitStatus.awaiting_triage,
                )
            )

            if existing_visit is not None:
                continue

            db.add(
                Visit(
                    patient_id=patient.id,
                    facility_id=facility.id,
                    reason_for_visit=demo_patient["reason_for_visit"],
                    status=VisitStatus.awaiting_triage,
                    checked_in_at=now - timedelta(minutes=demo_patient["wait_minutes"]),
                )
            )

        db.commit()


if __name__ == "__main__":
    seed_demo_users()
    seed_demo_triage_queue()
    print("Seeded demo users and triage queue.")
    print(f"Password for all demo users: {DEMO_PASSWORD}")
    for demo_user in DEMO_USERS:
        print(f"- {demo_user['role'].value}: {demo_user['email']}")
