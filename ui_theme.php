
<?php

function get_ui_theme($province){

    $themes = [

        "Eastern Cape" => [
            "primary" => "#0f4c3a",
            "primaryDark" => "#082a20",
            "sidebar" => "#145c46",
            "sidebarHover" => "#1f7c5d",
            "accent" => "#16a085",
            "text" => "#0b0f14",
            "bg" => "#f4f6f9"
        ],

        "Western Cape" => [
            "primary" => "#0b3b8c",
            "primaryDark" => "#072a63",
            "sidebar" => "#1243aa",
            "sidebarHover" => "#2c5de5",
            "accent" => "#2563eb",
            "text" => "#0b0f14",
            "bg" => "#f4f6f9"
        ],

        "Gauteng" => [
            "primary" => "#7a3b00",
            "primaryDark" => "#4d2600",
            "sidebar" => "#a65300",
            "sidebarHover" => "#cc6b00",
            "accent" => "#f59e0b",
            "text" => "#0b0f14",
            "bg" => "#f4f6f9"
        ],

        "KZN" => [
            "primary" => "#7b1e1e",
            "primaryDark" => "#4a1212",
            "sidebar" => "#a62828",
            "sidebarHover" => "#d63b3b",
            "accent" => "#ef4444",
            "text" => "#0b0f14",
            "bg" => "#f4f6f9"
        ],

        "Default" => [
            "primary" => "#0f4c3a",
            "primaryDark" => "#082a20",
            "sidebar" => "#145c46",
            "sidebarHover" => "#1f7c5d",
            "accent" => "#16a085",
            "text" => "#0b0f14",
            "bg" => "#f4f6f9"
        ]
    ];

    return $themes[$province] ?? $themes["Default"];
}
?>
