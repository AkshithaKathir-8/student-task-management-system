document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("assignmentForm");

    if (!form) return;

    const title = document.getElementById("title");
    const subject = document.getElementById("subject");
    const dueDate = document.getElementById("due_date");

    // Prevent selecting a past date for a new assignment.
    const today = new Date();
    const localToday = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, "0"),
        String(today.getDate()).padStart(2, "0")
    ].join("-");

    dueDate.min = localToday;

    form.addEventListener("submit", function (event) {
        if (!title.value.trim() || !subject.value.trim()) {
            event.preventDefault();
            alert("Please enter both the assignment title and subject.");
            return;
        }

        if (!dueDate.value) {
            event.preventDefault();
            alert("Please select a due date.");
            return;
        }

        if (dueDate.value < localToday) {
            event.preventDefault();
            alert("Please select today or a future due date.");
            dueDate.focus();
        }
    });
});