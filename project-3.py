import random

options =["rock", "paper", "scissors"]

user_win = 0 
cp_wins = 0

while True:
    user_input = input("type rock/paper/scissors or q to quit: ").lower()
    if user_input == "q":
        break

    if user_input not in options:
        continue

    rn = random.randint(0,2)
    #rock - 0, paper - 1, scissors - 2
    cp_pick = options[rn]
    print("computer has picked", cp_pick + ".")

    if user_input == "rock" and cp_pick == "scissors":
        print("You won!")
        user_win += 1
    elif user_input == "paper" and cp_pick == "rock":
        print("You won!")
        user_win += 1
    elif user_input == "scissors" and cp_pick == "paper":
        print("You won!")
        user_win += 1
    elif user_input == cp_pick:
        print("draw")
    else:
        print("You lost")
        cp_wins += 1

print(f"you won {user_win} times and the computer won {cp_wins} times")
print("Goodbye!")
        