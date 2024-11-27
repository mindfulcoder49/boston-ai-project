import tkinter as tk
import time
import threading

class MinimalCountdownApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Countdown Timer")
        self.root.geometry("200x100")
        self.root.resizable(False, False)

        # Countdown label
        self.countdown_label = tk.Label(root, text="60:00", font=("Helvetica", 48, "bold"), fg="blue")
        self.countdown_label.pack(expand=True)

        # Start countdown
        threading.Thread(target=self.run_countdown, args=(3600,), daemon=True).start()

    def run_countdown(self, total_seconds):
        while total_seconds > 0:
            mins, secs = divmod(total_seconds, 60)
            self.update_timer(f"{mins:02}:{secs:02}")
            time.sleep(1)
            total_seconds -= 1

        self.update_timer("00:00")

    def update_timer(self, time_str):
        self.countdown_label.config(text=time_str)

if __name__ == "__main__":
    root = tk.Tk()
    app = MinimalCountdownApp(root)
    root.mainloop()
