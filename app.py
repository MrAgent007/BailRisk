from flask import Flask, render_template, request
from bail_risk import BailRisk

app = Flask(__name__)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/calculator', methods=['GET', 'POST'])
def calculator():
    result = None
    if request.method == 'POST':
        name = request.form['name']
        bail_amount = request.form['bail_amount']
        flight_risk = request.form['flight_risk']
        violent_risk = request.form['violent_risk']
        court_appearances = request.form['court_appearances']
        risk = BailRisk(name, bail_amount, flight_risk, violent_risk, court_appearances)
        result = risk.get_risk_level()
    return render_template('calculator.html', result=result)

if __name__ == '__main__':
    app.run(debug=True)
