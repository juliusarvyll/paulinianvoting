import csv
import re

DEPT_LIMITS = {1: 257, 2: 73, 3: 90, 4: 158}

# Parse candidates from SQL
candidates = {}
with open('candidates.sql', 'r') as f:
    content = f.read()
    pattern = r'\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(?:(\d+)|NULL),\s*(?:(\d+)|NULL)'
    for match in re.finditer(pattern, content):
        cand_id, voter_id, pos_id, elec_id, course_id, dept_id = match.groups()
        candidates[int(cand_id)] = int(dept_id) if dept_id else None

# Analyze votes by department
dept_voters = {1: set(), 2: set(), 3: set(), 4: set()}
with open('votes_new.csv', 'r') as f:
    votes = list(csv.DictReader(f))
    
    for vote in votes:
        pos_id = int(vote['position_id'])
        if pos_id >= 11:  # Department positions
            cand_id = int(vote['candidate_id'])
            voter_id = int(vote['voter_id'])
            dept = candidates.get(cand_id)
            if dept:
                dept_voters[dept].add(voter_id)

# Report and identify excess voters
print("Department Analysis:")
voters_to_remove = set()
for dept_id in sorted(dept_voters.keys()):
    count = len(dept_voters[dept_id])
    limit = DEPT_LIMITS[dept_id]
    print(f"Dept {dept_id}: {count}/{limit} voters", end="")
    if count > limit:
        excess = count - limit
        print(f" - EXCEEDS by {excess}")
        # Remove highest voter IDs
        to_remove = sorted(dept_voters[dept_id], reverse=True)[:excess]
        voters_to_remove.update(to_remove)
    else:
        print(" - OK")

print(f"\nRemoving {len(voters_to_remove)} voters")

# Filter votes
valid_votes = [v for v in votes if int(v['position_id']) <= 10 or int(v['voter_id']) not in voters_to_remove]

print(f"Original: {len(votes)} | Valid: {len(valid_votes)} | Removed: {len(votes)-len(valid_votes)}")

with open('votes_corrected.csv', 'w', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=votes[0].keys())
    writer.writeheader()
    writer.writerows(valid_votes)

print("✓ votes_corrected.csv created")
