import sys
from pathlib import Path

from sqlalchemy import select

sys.path.append(str(Path(__file__).resolve().parents[1]))

from app.core.security import hash_password
from app.db.base import Base
from app.db.models import Facility, User
from app.db.models.user import AccountType, StaffRole
from app.db.session import SessionLocal, engine


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
    Base.metadata.create_all(bind=engine)
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


if __name__ == "__main__":
    seed_demo_users()
    print("Seeded demo users.")
    print(f"Password for all demo users: {DEMO_PASSWORD}")
    for demo_user in DEMO_USERS:
        print(f"- {demo_user['role'].value}: {demo_user['email']}")
