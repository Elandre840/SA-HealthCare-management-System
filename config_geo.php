
<?php
// config_geo.php
// Edit emblem filenames + colors anytime. Put emblem images in /assets/emblems/

$PROVINCES = [
  "Eastern Cape" => [
    "accent" => "#166534",
    "accent2" => "#14532d",
    "emblem" => "eastern_cape.png",
    "cities" => ["Gqeberha", "East London", "Mthatha", "Bhisho", "Grahamstown", "Queenstown", "Port St Johns"]
  ],
  "Free State" => [
    "accent" => "#7c3aed",
    "accent2" => "#6d28d9",
    "emblem" => "free_state.png",
    "cities" => ["Bloemfontein", "Welkom", "Bethlehem", "Kroonstad", "Sasolburg"]
  ],
  "Gauteng" => [
    "accent" => "#1d4ed8",
    "accent2" => "#1e40af",
    "emblem" => "gauteng.png",
    "cities" => ["Johannesburg", "Pretoria", "Soweto", "Tshwane", "Ekurhuleni", "Midrand"]
  ],
  "KwaZulu-Natal" => [
    "accent" => "#0ea5e9",
    "accent2" => "#0284c7",
    "emblem" => "kwazulu_natal.png",
    "cities" => ["Durban", "Pietermaritzburg", "Richards Bay", "Newcastle", "Ladysmith", "Ulundi"]
  ],
  "Limpopo" => [
    "accent" => "#dc2626",
    "accent2" => "#b91c1c",
    "emblem" => "limpopo.png",
    "cities" => ["Polokwane", "Tzaneen", "Thohoyandou", "Giyani", "Makhado"]
  ],
  "Mpumalanga" => [
    "accent" => "#f97316",
    "accent2" => "#ea580c",
    "emblem" => "mpumalanga.png",
    "cities" => ["Mbombela", "Emalahleni", "Secunda", "Standerton", "Sabie"]
  ],
  "Northern Cape" => [
    "accent" => "#d97706",
    "accent2" => "#b45309",
    "emblem" => "northern_cape.png",
    "cities" => ["Kimberley", "Upington", "Springbok", "De Aar", "Kuruman", "Warrenton"]
  ],
  "North West" => [
    "accent" => "#059669",
    "accent2" => "#047857",
    "emblem" => "north_west.png",
    "cities" => ["Mahikeng", "Rustenburg", "Klerksdorp", "Potchefstroom", "Brits"]
  ],
  "Western Cape" => [
    "accent" => "#0f766e",
    "accent2" => "#115e59",
    "emblem" => "western_cape.png",
    "cities" => ["Cape Town", "Stellenbosch", "George", "Paarl", "Worcester", "Mossel Bay"]
  ],
];

// Facility types (what the site is)
$FACILITY_TYPES = [
  "Clinic",
  "Hospital",
  "Doctor Practice",
  "Pharmacy",
  "Radiology Centre"
];

// Roles (user workflow)
$ROLES = [
  "Reception",
  "Nurse",
  "Doctor",
  "Pharmacist",
  "Radiologist",
  "Admin"
];
