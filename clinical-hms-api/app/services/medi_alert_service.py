import logging
from pathlib import Path

from app.db.models.patient import Patient
from app.db.models.user import User
from app.schemas.triage import VitalsCreate

logger = logging.getLogger(__name__)

LOG_DIR = Path(__file__).resolve().parents[2] / "logs"
MEDI_ALERT_LOG = LOG_DIR / "medi_alert.log"


def trigger_medi_alert(
    *,
    patient: Patient,
    visit_id: int,
    vitals: VitalsCreate,
    nurse: User,
) -> None:
    """Log emergency triage alerts to a local file.

    Production stub: replace the file write with a call to an email/SMS
    gateway (e.g. Twilio, SendGrid) or push a message onto a Redis queue
    for a background worker to dispatch. The file log is kept as a fallback
    audit trail even after the real notification pipeline is wired up.
    """
    LOG_DIR.mkdir(parents=True, exist_ok=True)

    message = (
        f"MediAlert RED triage | visit={visit_id} | patient={patient.full_name} "
        f"(folder {patient.folder_number}) | nurse={nurse.full_name} | "
        f"BP={vitals.blood_pressure_systolic}/{vitals.blood_pressure_diastolic} | "
        f"pulse={vitals.pulse_rate} | SpO2={vitals.oxygen_saturation}% | "
        f"notes={vitals.triage_notes or 'None'}"
    )

    logger.warning(message)

    with MEDI_ALERT_LOG.open("a", encoding="utf-8") as log_file:
        log_file.write(f"{message}\n")
