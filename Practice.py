import random

MAXLINES = 3
MAXBET = 100
MINBET = 10

ROWS = 3
COLS = 3

symbol_count = {
        "A" : 3,
        "B" : 5,
        "C" : 5,
        "D" : 6
}

symbol_value = {
        "A" : 3,
        "B" : 4,
        "C" : 5,
        "D" : 2
}


def checkWinnings(columns,lines,bet,values):
        winnings = 0
        winning_lines = []
        for line in range(lines):
                symbol = columns[0][line]
                for column in columns:
                    symbol_to_check = column[line]
                    if symbol!= symbol_to_check:
                            break
                else:
                       winnings += values[symbol] * bet
                       winning_lines.append(line + 1)
        return winnings, winning_lines

        

def machineSpin(rows,cols,symbols):
        all_symbols = []
        for symbol,symbol_count in symbols.items():
                for _ in range(symbol_count):
                    all_symbols.append(symbol)

        columns = []
        for _ in range(cols):
            column = []
            current_symbols = all_symbols[:]
            for _ in range(rows):
                value = random.choice(current_symbols)
                current_symbols.remove(value)
                column.append(value)
            columns.append(column)
        return columns

def printSlotMachine(columns):
        for row in range(len(columns[0])):
                for i, column in enumerate(columns):
                        if i != len(columns) - 1:
                                print(column[row], end=" | ")
                        else:
                                print(column[row], end="")
                print()

def getDeposit():
        while True:
            deposit = input("Please enter the aumount you would like to deposit $ ")
            if deposit.isdigit():
                deposit = int(deposit)
                if  deposit > 0:
                    break
                else:
                    print("enter a number greater than 0.")
            else:
                  
                  print("enter a number.")
        return deposit

def get_lines():
    while True:
            lines = input("Please enter the number of line u whould like ot bet on(1-" + str(MAXLINES) +")? ")
            if lines.isdigit():
                lines = int(lines)
                if  1 <= lines <= MAXLINES:
                    break
                else:
                    print("enter a number greater than 0 and less than 4.")
            else:
                  print("enter a number.")
    return lines

def get_bet():
    while True:
            bet = input("Enter the amount you would like to bet on each line? $ ")
            if bet.isdigit():
                bet = int(bet)
                if  MINBET <= bet <= MAXBET:
                    break
                else:
                    print(f"Amount must be between {MINBET} - {MAXBET}.")
            else:
                  print("enter a number.")
    return bet

def spin(balance):
        lines = get_lines()
        while True:
                bets  = get_bet()
                totalBet = lines * bets

                if totalBet > balance:
                        print(f"You do not have enough to bet that amount, your current balance is : {balance}")
                else:
                        break
    
        print(f"You are betting ${bets} on {lines} lines. total be is equal to ${totalBet}")

        slots = machineSpin(ROWS,COLS,symbol_count)
        printSlotMachine(slots)
        winnings, winning_lines = checkWinnings(slots, lines, bets, symbol_value)
        print(f"You won ${winnings}.")
        print(f"You won on,{winning_lines}")
        return winnings - totalBet

def main():
        balance = getDeposit()
        while True:
                print(f"your current balance is ${balance}")
                answers = input("press enter to spin(q to quit)")
                if answers == "q":
                        break
                else:
                    balance += spin(balance)

        print(f"you are left with balance ${balance}")

    

   #symbol_count - dict
    

main()
     
          	
  
