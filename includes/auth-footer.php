        </div>

    </div>

</div>

<script>

function togglePassword(id)
{
    const field =
        document.getElementById(id);

    if (!field) {
        return;
    }

    if (field.type === "password")
    {
        field.type = "text";
    }
    else
    {
        field.type = "password";
    }
}

</script>

</body>
</html>