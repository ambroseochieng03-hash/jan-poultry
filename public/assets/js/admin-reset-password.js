/*
|--------------------------------------------------------------------------
| JAN POULTRY
| ADMIN RESET PASSWORD VALIDATION
|--------------------------------------------------------------------------
*/


document.addEventListener(
    "DOMContentLoaded",
    function(){


        const password =
            document.getElementById(
                "password"
            );


        const confirmPassword =
            document.getElementById(
                "password_confirmation"
            );


        const submitButton =
            document.querySelector(
                ".reset-btn"
            );


        if(
            !password ||
            !confirmPassword
        ){

            return;

        }



        const rules = {

            length:
                document.getElementById(
                    "lengthRule"
                ),


            upper:
                document.getElementById(
                    "upperRule"
                ),


            lower:
                document.getElementById(
                    "lowerRule"
                ),


            number:
                document.getElementById(
                    "numberRule"
                ),


            symbol:
                document.getElementById(
                    "symbolRule"
                )

        };



        function updateRule(
            element,
            passed,
            text
        ){

            if(!element){
                return;
            }


            if(passed){

                element.innerHTML =
                    "✅ " + text;

                element.style.color =
                    "#28a745";


            }else{


                element.innerHTML =
                    "❌ " + text;


                element.style.color =
                    "#dc3545";


            }

        }




        function validatePassword()
        {


            const value =
                password.value;



            const checks = {


                length:
                    value.length >= 12,


                upper:
                    /[A-Z]/.test(value),


                lower:
                    /[a-z]/.test(value),


                number:
                    /[0-9]/.test(value),


                symbol:
                    /[^A-Za-z0-9]/.test(value)

            };



            updateRule(
                rules.length,
                checks.length,
                "At least 12 characters"
            );


            updateRule(
                rules.upper,
                checks.upper,
                "One uppercase letter"
            );


            updateRule(
                rules.lower,
                checks.lower,
                "One lowercase letter"
            );


            updateRule(
                rules.number,
                checks.number,
                "One number"
            );


            updateRule(
                rules.symbol,
                checks.symbol,
                "One special character"
            );



            return (

                checks.length &&
                checks.upper &&
                checks.lower &&
                checks.number &&
                checks.symbol

            );

        }




        function validateForm()
        {


            const passwordValid =
                validatePassword();



            const matching =
                password.value ===
                confirmPassword.value
                &&
                confirmPassword.value !== "";



            if(
                submitButton
            ){

                submitButton.disabled =
                    !(
                        passwordValid
                        &&
                        matching
                    );


                if(
                    submitButton.disabled
                ){

                    submitButton.style.opacity =
                        "0.5";

                    submitButton.style.cursor =
                        "not-allowed";


                }else{


                    submitButton.style.opacity =
                        "1";

                    submitButton.style.cursor =
                        "pointer";


                }

            }

        }




        password.addEventListener(
            "input",
            validateForm
        );


        confirmPassword.addEventListener(
            "input",
            validateForm
        );



        validateForm();


    }

);

/*
|--------------------------------------------------------------------------
| JAN POULTRY
| Admin Reset Password Live Validation
|--------------------------------------------------------------------------


document.addEventListener(
    "DOMContentLoaded",
    function () {

        const password =
            document.getElementById("password");

        const confirmPassword =
            document.getElementById("password_confirmation");

        const submitBtn =
            document.querySelector(".reset-btn");

        const rules = {
            length: document.getElementById("lengthRule"),
            upper: document.getElementById("upperRule"),
            lower: document.getElementById("lowerRule"),
            number: document.getElementById("numberRule"),
            symbol: document.getElementById("symbolRule")
        };

        function validatePassword(value) {

            const checks = {
                length: value.length >= 12,
                upper: /[A-Z]/.test(value),
                lower: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                symbol: /[^A-Za-z0-9]/.test(value)
            };

            updateRuleUI(checks);

            return Object.values(checks).every(Boolean);
        }

        function updateRuleUI(checks) {

            updateRule(rules.length, checks.length, "At least 12 characters");
            updateRule(rules.upper, checks.upper, "One uppercase letter");
            updateRule(rules.lower, checks.lower, "One lowercase letter");
            updateRule(rules.number, checks.number, "One number");
            updateRule(rules.symbol, checks.symbol, "One special character");

        }

        function updateRule(el, passed, text) {

            if (!el) return;

            el.textContent =
                (passed ? "✓ " : "✖ ") + text;

            el.style.color =
                passed ? "#28a745" : "#c5cedb";
        }

        function validateMatch() {

            if (!confirmPassword) return false;

            if (password.value !== confirmPassword.value) {

                confirmPassword.style.borderColor =
                    "#dc3545";

                return false;

            }

            confirmPassword.style.borderColor =
                "#28a745";

            return true;
        }

        function updateButtonState() {

            const isValid =
                validatePassword(password.value) &&
                validateMatch();

            submitBtn.disabled = !isValid;

            submitBtn.style.opacity =
                isValid ? "1" : "0.5";

            submitBtn.style.cursor =
                isValid ? "pointer" : "not-allowed";
        }

        if (password) {

            password.addEventListener("input", updateButtonState);

        }

        if (confirmPassword) {

            confirmPassword.addEventListener("input", updateButtonState);

        }

        // initial state
        submitBtn.disabled = true;
        submitBtn.style.opacity = "0.5";
        submitBtn.style.cursor = "not-allowed";

    }
); */
