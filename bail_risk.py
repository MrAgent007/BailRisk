class BailRisk:
    def __init__(self, name, bail_amount, flight_risk, violent_risk, court_appearances):
        # Validate inputs
        self.name = name.strip() if name.strip() else "Unknown"
        self.bail_amount = self._validate_number(bail_amount, 0, 1000000)  # Cap at 1M
        self.flight_risk = self._validate_number(flight_risk, 0, 100)  # 0-100 scale
        self.violent_risk = self._validate_number(violent_risk, 0, 100)  # 0-100 scale
        self.court_appearances = self._validate_number(court_appearances, 0, 50, is_int=True)  # 0-50 max
        self.score = 0

    def _validate_number(self, value, min_val, max_val, is_int=False):
        try:
            num = int(value) if is_int else float(value)
            return max(min_val, min(num, max_val))  # Clamp between min and max
        except (ValueError, TypeError):
            return min_val  # Default to min if invalid

    def calculate_risk(self):
        bail_factor = min(self.bail_amount, 100000) / 100000 * 100  # Cap at 100K for scoring
        flight_factor = self.flight_risk
        violent_factor = self.violent_risk
        appearance_factor = max(0, (10 - self.court_appearances)) * 10  # 0-10 scale inverted
        self.score = (bail_factor * 0.2) + (flight_factor * 0.3) + (violent_factor * 0.3) + (appearance_factor * 0.2)

    def get_risk_level(self):
        self.calculate_risk()
        if self.score < 30:
            return f"{self.name}: Low Risk ({self.score:.2f})"
        elif 30 <= self.score <= 70:
            return f"{self.name}: Medium Risk ({self.score:.2f})"
        else:
            return f"{self.name}: High Risk ({self.score:.2f})"
